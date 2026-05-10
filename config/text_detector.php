<?php

    return [
        'max_text_length' => env('TEXT_DETECTOR_MAX_LENGTH', 12000),
        'default_model' => env('TEXT_DETECTOR_DEFAULT_MODEL', 'svm'),
        'request_timeout' => env('TEXT_DETECTOR_REQUEST_TIMEOUT', 30),

        /*
        |-------------------------------------------------------------
        | Endpoint de inferencia
        |-------------------------------------------------------------
        | Este endpoint debe responder JSON con algo como:
        | {
        |   "clasificacion": "IA",
        |   "confianza": 0.89,
        |   "probabilidades": {
        |      "Humano": 0.11,
        |      "IA": 0.89
        |   }
        | }
        */
        'inference_url' => env('TEXT_DETECTOR_INFERENCE_URL', 'http://127.0.0.1:8001/api/infer'),

        'supported_models' => [
            'svm',
            'naive_bayes',
            'comparar',
        ],
    ];
?>