<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassifyTextRequest;
use App\Services\TextInferenceService;
use Illuminate\Http\JsonResponse;
use Throwable;

class TextClassificationController extends Controller
{
    public function __construct(
        private readonly TextInferenceService $inferenceService
    ) {}

    /**
     * POST /api/clasificar-texto
     *
     * Body JSON:
     *   {
     *     "texto": "El texto a analizar...",
     *     "modelo": "svm" | "naive_bayes" | "comparar"   (opcional, default: svm)
     *   }
     *
     * Responses:
     *   200 - Análisis exitoso
     *   422 - Validación fallida
     *   502 - Servicio de inferencia no disponible
     *   500 - Error interno
     */
    public function analyze(ClassifyTextRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $modelo = $validated['modelo'] ?? config('text_detector.default_model');

        try {
            $resultado = $this->inferenceService->analyze(
                texto: $validated['texto'],
                modelo: $modelo
            );

            return response()->json([
                'success' => true,
                'message' => 'Texto analizado correctamente.',
                'data'    => $resultado,
            ], 200);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 502);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar la solicitud.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * GET /api/health
     *
     * Verifica el estado del servicio Laravel y del microservicio Python.
     */
    public function health(): JsonResponse
    {
        $inferenceUrl = config('text_detector.inference_url');
        $pythonOk     = false;
        $pythonStatus = null;
        $pythonError  = null;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->get(str_replace('/api/infer', '/health', $inferenceUrl));

            $pythonOk     = $response->successful();
            $pythonStatus = $response->json();
        } catch (Throwable $e) {
            $pythonError = $e->getMessage();
        }

        $httpCode = $pythonOk ? 200 : 503;

        return response()->json([
            'success' => $pythonOk,
            'status'  => $pythonOk ? 'ok' : 'degraded',
            'services' => [
                'laravel' => [
                    'status' => 'ok',
                    'version' => app()->version(),
                ],
                'python_inference' => [
                    'status'  => $pythonOk ? 'ok' : 'unavailable',
                    'url'     => $inferenceUrl,
                    'details' => $pythonStatus,
                    'error'   => $pythonError,
                ],
            ],
        ], $httpCode);
    }

    /**
     * GET /api/modelos
     *
     * Devuelve los modelos disponibles y la configuración actual.
     */
    public function models(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'modelos_disponibles' => config('text_detector.supported_models'),
                'modelo_default'      => config('text_detector.default_model'),
                'max_caracteres'      => config('text_detector.max_text_length'),
                'min_caracteres'      => 10,
            ],
        ]);
    }
}
