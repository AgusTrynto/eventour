import argparse
import json
import os

os.environ.setdefault("TF_CPP_MIN_LOG_LEVEL", "2")


def get_layer_order(config):
    layers = config["config"]["layers"]
    dense_names = []
    bn_names = []
    for l in layers:
        name = l["config"]["name"]
        if l["class_name"] == "Dense":
            dense_names.append(name)
        elif l["class_name"] == "BatchNormalization":
            bn_names.append(name)
    return dense_names, bn_names


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


def get_dense_weights(model_weights, name):
    g = model_weights[name][list(model_weights[name].keys())[0]][name]
    return find_dataset(g, "kernel"), find_dataset(g, "bias")


def get_bn_params(model_weights, name):
    g = model_weights[name][list(model_weights[name].keys())[0]][name]
    return tuple(
        find_dataset(g, k) for k in ["gamma", "beta", "moving_mean", "moving_variance"]
    )


def export_sidecar(model_path, output_path):
    import h5py
    import numpy as np

    with h5py.File(model_path, "r") as h5_file:
        config = json.loads(h5_file.attrs["model_config"])
        mw = h5_file["model_weights"]
        dense_names, bn_names = get_layer_order(config)

        if not bn_names:
            # No BN layers — extract dense weights as-is
            layers = []
            for dn in dense_names:
                k, b = get_dense_weights(mw, dn)
                act = "linear"
                for l in config["config"]["layers"]:
                    if l["config"]["name"] == dn:
                        act = l["config"].get("activation", "linear")
                        break
                layers.append({"name": dn, "activation": act, "kernel": k.tolist(), "bias": b.tolist()})
        else:
            eps = 0.001
            for l in config["config"]["layers"]:
                if l["class_name"] == "BatchNormalization":
                    eps = l["config"].get("epsilon", 0.001)
                    break

            layers = []
            # First dense: no BN to fold in from a previous BN
            dn0 = dense_names[0]
            k0, b0 = get_dense_weights(mw, dn0)
            act0 = "linear"
            for l in config["config"]["layers"]:
                if l["config"]["name"] == dn0:
                    act0 = l["config"].get("activation", "linear")
                    break
            layers.append({"name": dn0, "activation": act0, "kernel": k0.tolist(), "bias": b0.tolist()})

            # Fold each BN into the following Dense layer
            for bn_name, dn in zip(bn_names, dense_names[1:]):
                g, b_bn, m, v = get_bn_params(mw, bn_name)
                A = g / np.sqrt(v + eps)
                B = b_bn - g * m / np.sqrt(v + eps)

                k, b = get_dense_weights(mw, dn)
                k_adj = k * A[:, np.newaxis]
                b_adj = (k.T @ B) + b

                act = "linear"
                for l in config["config"]["layers"]:
                    if l["config"]["name"] == dn:
                        act = l["config"].get("activation", "linear")
                        break
                layers.append({"name": dn, "activation": act, "kernel": k_adj.tolist(), "bias": b_adj.tolist()})

        sidecar = {"layers": layers}

    os.makedirs(os.path.dirname(output_path) or ".", exist_ok=True)
    with open(output_path, "w", encoding="utf-8") as handle:
        json.dump(sidecar, handle, indent=2)

    print(f"Exported folded weights to {output_path}")


def main():
    parser = argparse.ArgumentParser(
        description="Export NCBF .h5 weights to JSON sidecar (folds BatchNorm forward)."
    )
    parser.add_argument("--model", required=True, help="Path to .h5 model")
    parser.add_argument("--output", help="Output JSON path (default: <model>_weights.json)")
    args = parser.parse_args()

    if args.output:
        output_path = args.output
    else:
        base, _ = os.path.splitext(args.model)
        output_path = f"{base}_weights.json"

    export_sidecar(args.model, output_path)


if __name__ == "__main__":
    main()
