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
        ) {
        }

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
                    'data' => $resultado,
                ], 200);
            } catch (\RuntimeException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 502);
            } catch (Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Error interno al procesar la solicitud.',
                ], 500);
            }
        }
    }