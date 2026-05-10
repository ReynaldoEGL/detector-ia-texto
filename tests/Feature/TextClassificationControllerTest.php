<?php

    namespace Tests\Feature;

    use Illuminate\Support\Facades\Http;
    use Tests\TestCase;

    class TextClassificationControllerTest extends TestCase
    {
        public function test_it_classifies_text_with_svm(): void
        {
            Http::fake([
                '*' => Http::response([
                    'clasificacion' => 'IA',
                    'confianza' => 0.91,
                    'probabilidades' => [
                        'Humano' => 0.09,
                        'IA' => 0.91,
                    ],
                ], 200),
            ]);

            $response = $this->postJson('/api/clasificar-texto', [
                'texto' => 'Este texto fue escrito para probar la API del detector.',
                'modelo' => 'svm',
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.modelo', 'svm')
                ->assertJsonPath('data.prediccion.clasificacion', 'IA');
        }
    }