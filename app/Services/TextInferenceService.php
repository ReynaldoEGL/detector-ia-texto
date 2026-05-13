<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TextInferenceService
{
    public function __construct(
        private readonly TextNormalizationService $normalizer
    ) {
    }

    public function analyze(string $texto, string $modelo = 'svm'): array
    {
        $textoLimpio = $this->normalizer->normalize($texto);

        if ($textoLimpio === '') {
            throw new RuntimeException('El texto quedó vacío después de la normalización.');
        }

        if ($modelo === 'comparar') {
            $svm = $this->predict($textoLimpio, 'svm');
            $nb = $this->predict($textoLimpio, 'naive_bayes');

            return [
                'texto_original' => $texto,
                'texto_limpio' => $textoLimpio,
                'modelo' => 'comparar',
                'comparacion' => [
                    'svm' => $svm,
                    'naive_bayes' => $nb,
                ],
                'modelo_recomendado' => ($svm['confianza'] ?? 0) >= ($nb['confianza'] ?? 0)
                    ? 'svm'
                    : 'naive_bayes',
            ];
        }

        return [
            'texto_original' => $texto,
            'texto_limpio' => $textoLimpio,
            'modelo' => $modelo,
            'prediccion' => $this->predict($textoLimpio, $modelo),
        ];
    }

    private function predict(string $textoLimpio, string $modelo): array
    {
        $url = config('text_detector.inference_url');

        if (!is_string($url) || trim($url) === '') {
            throw new RuntimeException('TEXT_DETECTOR_INFERENCE_URL no está configurada.');
        }

        $response = Http::acceptJson()
            ->timeout((int) config('text_detector.request_timeout'))
            ->retry(1, 250)
            ->asJson()
            ->post($url, [
                'texto' => $textoLimpio,
                'modelo' => $modelo,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'El servicio de inferencia respondió con HTTP ' .
                $response->status() .
                ': ' .
                $response->body()
            );
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException('La respuesta del servicio de inferencia no es JSON válido.');
        }

        $source = $this->extractPredictionSource($payload, $modelo);

        $clasificacion = $source['clasificacion'] ?? $source['label'] ?? null;
        $confianza = $source['confianza'] ?? $source['confidence'] ?? null;
        $probabilidades = $source['probabilidades'] ?? $source['probabilities'] ?? null;

        if (!is_string($clasificacion) || trim($clasificacion) === '') {
            throw new RuntimeException(
                'La respuesta del servicio de inferencia no incluye clasificación. Respuesta recibida: ' .
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        return [
            'modelo' => $modelo,
            'clasificacion' => $clasificacion,
            'confianza' => is_numeric($confianza) ? round((float) $confianza, 4) : null,
            'probabilidades' => is_array($probabilidades) ? $probabilidades : null,
            'raw' => $payload,
        ];
    }

    private function extractPredictionSource(array $payload, string $modelo): array
    {
        // Caso 1: respuesta anidada estándar
        if (isset($payload['prediccion']) && is_array($payload['prediccion'])) {
            return $payload['prediccion'];
        }

        // Caso 2: respuesta de comparación
        if (isset($payload['comparacion']) && is_array($payload['comparacion'])) {
            $node = $payload['comparacion'][$modelo] ?? null;
            if (is_array($node)) {
                return $node;
            }
        }

        // Caso 3: algunas respuestas pueden venir dentro de data
        if (isset($payload['data']) && is_array($payload['data'])) {
            $data = $payload['data'];

            if (isset($data['prediccion']) && is_array($data['prediccion'])) {
                return $data['prediccion'];
            }

            if (isset($data['comparacion']) && is_array($data['comparacion'])) {
                $node = $data['comparacion'][$modelo] ?? null;
                if (is_array($node)) {
                    return $node;
                }
            }

            return $data;
        }

        // Caso 4: respuesta plana
        return $payload;
    }
}