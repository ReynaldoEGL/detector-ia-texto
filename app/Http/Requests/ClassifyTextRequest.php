<?php

    namespace App\Http\Requests;

    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Validation\Rule;

    class ClassifyTextRequest extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }

        public function rules(): array
        {
            return [
                'texto' => [
                    'required',
                    'string',
                    'min:10',
                    'max:' . config('text_detector.max_text_length'),
                ],
                'modelo' => [
                    'sometimes',
                    'string',
                    Rule::in(config('text_detector.supported_models')),
                ],
            ];
        }

        public function messages(): array
        {
            return [
                'texto.required' => 'Debes enviar un texto para analizar.',
                'texto.string' => 'El texto debe ser una cadena válida.',
                'texto.min' => 'El texto debe tener al menos :min caracteres.',
                'texto.max' => 'El texto excede la longitud máxima permitida.',
                'modelo.in' => 'El modelo solicitado no es válido.',
            ];
        }
    }