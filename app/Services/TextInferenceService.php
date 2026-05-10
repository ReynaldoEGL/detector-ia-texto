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
                    'modelo_recomendado' => $this->recommendModel($svm, $nb),
                ];
            }

            $prediccion = $this->predict($textoLimpio, $modelo);

            return [
                'texto_original' => $texto,
                'texto_limpio' => $textoLimpio,
                'modelo' => $modelo,
                'prediccion' => $prediccion,
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
                ->asJson()
                ->post($url, [
                    'texto' => $textoLimpio,
                    'modelo' => $modelo,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'El servicio de inferencia respondió con estado HTTP ' . $response->status() . '.'
                );
            }

            $payload = $response->json();

            if (!is_array($payload)) {
                throw new RuntimeException('La respuesta del servicio de inferencia no es JSON válido.');
            }

            $clasificacion = $payload['clasificacion'] ?? $payload['label'] ?? null;
            $confianza = $payload['confianza'] ?? $payload['confidence'] ?? null;
            $probabilidades = $payload['probabilidades'] ?? $payload['probabilities'] ?? null;

            if (!is_string($clasificacion) || $clasificacion === '') {
                throw new RuntimeException('La respuesta del servicio de inferencia no incluye clasificacion.');
            }

            return [
                'modelo' => $modelo,
                'clasificacion' => $clasificacion,
                'confianza' => is_numeric($confianza) ? round((float) $confianza, 4) : null,
                'probabilidades' => is_array($probabilidades) ? $probabilidades : null,
                'raw' => $payload,
            ];
        }

        private function recommendModel(array $svm, array $nb): string
        {
            $svmConfidence = (float) ($svm['confianza'] ?? 0);
            $nbConfidence = (float) ($nb['confianza'] ?? 0);

            return $svmConfidence >= $nbConfidence ? 'svm' : 'naive_bayes';
        }
    }