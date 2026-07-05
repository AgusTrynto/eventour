import argparse
import json
import os
from datetime import datetime, timezone

os.environ.setdefault("TF_CPP_MIN_LOG_LEVEL", "2")

import numpy as np
import tensorflow as tf


def load_dataset(path):
    with open(path, "r", encoding="utf-8") as handle:
        data = json.load(handle)

    samples = data.get("samples", [])
    if not samples:
        raise ValueError("Training dataset does not contain samples.")

    x = np.asarray([sample["features"] for sample in samples], dtype=np.float32)
    y = np.asarray([sample["label"] for sample in samples], dtype=np.float32)
    input_dim = int(data.get("input_dim") or x.shape[1])

    if x.ndim != 2 or x.shape[1] != input_dim:
        raise ValueError(f"Expected input_dim {input_dim}, got shape {x.shape}.")

    return data, x, y, input_dim


def split_train_validation(x, y, validation_ratio):
    indices = np.arange(len(y))
    rng = np.random.default_rng(42)
    rng.shuffle(indices)

    validation_size = max(1, int(len(indices) * validation_ratio))
    validation_indices = indices[:validation_size]
    train_indices = indices[validation_size:]

    if len(train_indices) == 0:
        return x, y, x, y

    return x[train_indices], y[train_indices], x[validation_indices], y[validation_indices]


def build_model(input_dim):
    model = tf.keras.Sequential(
        [
            tf.keras.layers.Input(shape=(input_dim,)),
            tf.keras.layers.Dense(64, activation="relu"),
            tf.keras.layers.Dropout(0.2),
            tf.keras.layers.Dense(32, activation="relu"),
            tf.keras.layers.Dropout(0.1),
            tf.keras.layers.Dense(16, activation="relu"),
            tf.keras.layers.Dense(1, activation="sigmoid"),
        ]
    )

    model.compile(
        optimizer=tf.keras.optimizers.Adam(learning_rate=0.001),
        loss="binary_crossentropy",
        metrics=["accuracy", tf.keras.metrics.AUC(name="auc")],
    )

    return model


def write_metadata(path, dataset, input_dim, history, evaluation):
    metadata = {
        "created_at": datetime.now(timezone.utc).isoformat(),
        "input_dim": input_dim,
        "event_vector_dim": dataset.get("event_vector_dim"),
        "max_price": dataset.get("max_price"),
        "positive_count": dataset.get("positive_count"),
        "negative_count": dataset.get("negative_count"),
        "final_train_loss": float(history.history["loss"][-1]),
        "final_train_accuracy": float(history.history["accuracy"][-1]),
        "validation_loss": float(evaluation[0]),
        "validation_accuracy": float(evaluation[1]),
        "validation_auc": float(evaluation[2]) if len(evaluation) > 2 else None,
    }

    with open(path, "w", encoding="utf-8") as handle:
        json.dump(metadata, handle, indent=2)


def main():
    parser = argparse.ArgumentParser(description="Train EvenTour NCBF Keras .h5 model.")
    parser.add_argument("--data", default="storage/app/recommendation/ncbf_training.json")
    parser.add_argument("--output", default="storage/app/recommendation/ncbf_model.h5")
    parser.add_argument("--metadata", default="storage/app/recommendation/ncbf_model_meta.json")
    parser.add_argument("--epochs", type=int, default=80)
    parser.add_argument("--batch-size", type=int, default=16)
    parser.add_argument("--validation-ratio", type=float, default=0.2)
    args = parser.parse_args()

    dataset, x, y, input_dim = load_dataset(args.data)
    x_train, y_train, x_val, y_val = split_train_validation(x, y, args.validation_ratio)

    model = build_model(input_dim)
    callbacks = [
        tf.keras.callbacks.EarlyStopping(
            monitor="val_loss",
            patience=10,
            restore_best_weights=True,
        )
    ]

    history = model.fit(
        x_train,
        y_train,
        validation_data=(x_val, y_val),
        epochs=args.epochs,
        batch_size=args.batch_size,
        callbacks=callbacks,
        verbose=1,
    )

    evaluation = model.evaluate(x_val, y_val, verbose=0)
    os.makedirs(os.path.dirname(args.output), exist_ok=True)
    model.save(args.output)

    os.makedirs(os.path.dirname(args.metadata), exist_ok=True)
    write_metadata(args.metadata, dataset, input_dim, history, evaluation)

    print(f"Saved model to {args.output}")
    print(f"Saved metadata to {args.metadata}")


if __name__ == "__main__":
    main()
