import argparse
import json
import os
import sys

os.environ.setdefault("TF_CPP_MIN_LOG_LEVEL", "2")

import numpy as np
import tensorflow as tf


def main():
    parser = argparse.ArgumentParser(description="Predict EvenTour NCBF scores with a Keras .h5 model.")
    parser.add_argument("--model", required=True)
    args = parser.parse_args()

    payload = json.load(sys.stdin)
    samples = payload.get("samples", [])

    if not samples:
        print(json.dumps({"scores": []}))
        return

    features = np.asarray([sample["features"] for sample in samples], dtype=np.float32)
    model = tf.keras.models.load_model(args.model)
    predictions = model.predict(features, verbose=0).reshape(-1)

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
