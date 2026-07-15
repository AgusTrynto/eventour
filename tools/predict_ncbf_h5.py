import argparse
import json
import math
import os
import shutil
import sys
import tempfile

os.environ.setdefault("TF_CPP_MIN_LOG_LEVEL", "2")


def strip_unsupported_config(config):
    if isinstance(config, dict):
        return {
            key: strip_unsupported_config(value)
            for key, value in config.items()
            if key != "quantization_config"
        }

    if isinstance(config, list):
        return [strip_unsupported_config(value) for value in config]

    return config


def write_compatible_h5_copy(model_path):
    import h5py

    handle = tempfile.NamedTemporaryFile(suffix=".h5", delete=False)
    handle.close()
    shutil.copyfile(model_path, handle.name)

    with h5py.File(handle.name, "r+") as h5_file:
        model_config = h5_file.attrs.get("model_config")

        if model_config is None:
            return handle.name

        if isinstance(model_config, bytes):
            model_config = model_config.decode("utf-8")

        cleaned_config = strip_unsupported_config(json.loads(model_config))
        h5_file.attrs["model_config"] = json.dumps(cleaned_config).encode("utf-8")

    return handle.name


def load_model(model_path):
    import tensorflow as tf

    try:
        return tf.keras.models.load_model(model_path, compile=False)
    except (TypeError, ValueError) as exception:
        if "quantization_config" not in str(exception):
            raise

    compatible_path = write_compatible_h5_copy(model_path)

    try:
        return tf.keras.models.load_model(compatible_path, compile=False)
    finally:
        try:
            os.remove(compatible_path)
        except OSError:
            pass


def decode_json_attr(value):
    if isinstance(value, bytes):
        value = value.decode("utf-8")

    return json.loads(value)


def find_dataset(group, dataset_name):
    import h5py
    import numpy as np

    found = None

    def visitor(name, obj):
        nonlocal found

        if found is None and isinstance(obj, h5py.Dataset) and name.rsplit("/", 1)[-1] == dataset_name:
            found = obj[()]

    group.visititems(visitor)

    if found is None:
        raise ValueError(f"Missing {dataset_name} dataset in {group.name}.")

    return np.asarray(found, dtype=np.float32)


def dense_layer_configs(h5_file):
    model_config = h5_file.attrs.get("model_config")

    if model_config is None:
        raise ValueError("H5 model does not contain model_config.")

    config = decode_json_attr(model_config)
    layers = config.get("config", {}).get("layers", [])
    dense_layers = []

    for layer in layers:
        if layer.get("class_name") != "Dense":
            continue

        layer_config = layer.get("config", {})
        dense_layers.append((
            layer_config.get("name"),
            layer_config.get("activation", "linear"),
        ))

    if not dense_layers:
        raise ValueError("H5 model does not contain Dense layers.")

    return dense_layers


def activate(values, activation):
    if activation == "relu":
        return np.maximum(values, 0.0)

    if activation == "sigmoid":
        return 1.0 / (1.0 + np.exp(-np.clip(values, -60.0, 60.0)))

    if activation in ("linear", None):
        return values

    raise ValueError(f"Unsupported activation: {activation}")


def predict_with_h5py(model_path, features):
    import h5py
    import numpy as np

    values = np.asarray(features, dtype=np.float32)

    with h5py.File(model_path, "r") as h5_file:
        model_weights = h5_file["model_weights"]

        for layer_name, activation in dense_layer_configs(h5_file):
            if layer_name not in model_weights:
                raise ValueError(f"Missing weights for layer {layer_name}.")

            layer_group = model_weights[layer_name]
            kernel = find_dataset(layer_group, "kernel")
            bias = find_dataset(layer_group, "bias")
            values = activate((values @ kernel) + bias, activation)

    return values.reshape(-1)


def weights_sidecar_path(model_path):
    base, _extension = os.path.splitext(model_path)

    return f"{base}_weights.json"


def activate_scalar(value, activation):
    if activation == "relu":
        return value if value > 0.0 else 0.0

    if activation == "sigmoid":
        value = max(-60.0, min(60.0, value))

        return 1.0 / (1.0 + math.exp(-value))

    if activation in ("linear", None):
        return value

    raise ValueError(f"Unsupported activation: {activation}")


def predict_with_sidecar(model_path, features):
    sidecar_path = weights_sidecar_path(model_path)

    if not os.path.isfile(sidecar_path):
        raise FileNotFoundError(sidecar_path)

    with open(sidecar_path, "r", encoding="utf-8") as handle:
        model = json.load(handle)

    predictions = []

    for row in features:
        values = [float(value) for value in row]

        for layer in model["layers"]:
            kernel = layer["kernel"]
            bias = layer["bias"]
            activation = layer.get("activation", "linear")
            next_values = []

            for unit_index, bias_value in enumerate(bias):
                total = float(bias_value)

                for input_index, input_value in enumerate(values):
                    total += input_value * float(kernel[input_index][unit_index])

                next_values.append(activate_scalar(total, activation))

            values = next_values

        predictions.append(values[0] if values else 0.0)

    return predictions


def predict_scores(model_path, features):
    try:
        return predict_with_sidecar(model_path, features)
    except Exception:
        pass

    try:
        return predict_with_h5py(model_path, features)
    except Exception:
        import numpy as np

        model = load_model(model_path)
        features = np.asarray(features, dtype=np.float32)

        return model.predict(features, verbose=0).reshape(-1)


def main():
    parser = argparse.ArgumentParser(description="Predict EvenTour NCBF scores with a Keras .h5 model.")
    parser.add_argument("--model", required=True)
    args = parser.parse_args()

    payload = json.load(sys.stdin)
    samples = payload.get("samples", [])

    if not samples:
        print(json.dumps({"scores": []}))
        return

    features = [sample["features"] for sample in samples]
    predictions = predict_scores(args.model, features)

    scores = [
        {
            "event_id": int(sample["event_id"]),
            "score": float(max(0.0, min(1.0, predictions[index]))),
        }
        for index, sample in enumerate(samples)
    ]

    print(json.dumps({"scores": scores}))


if __name__ == "__main__":
    main()
