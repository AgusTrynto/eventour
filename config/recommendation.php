<?php

return [
    'h5' => [
        'enabled' => env('NCBF_H5_ENABLED', true),
        'python' => env('NCBF_PYTHON', 'python'),
        'model_path' => env('NCBF_H5_MODEL_PATH', storage_path('app/recommendation/ncbf_model.h5')),
        'metadata_path' => env('NCBF_H5_METADATA_PATH', storage_path('app/recommendation/ncbf_model_meta.json')),
        'predict_script' => env('NCBF_H5_PREDICT_SCRIPT', base_path('tools/predict_ncbf_h5.py')),
        'timeout' => (int) env('NCBF_H5_TIMEOUT', 30),
    ],
];
