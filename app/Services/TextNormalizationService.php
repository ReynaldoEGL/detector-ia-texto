<?php

    namespace App\Services;

    class TextNormalizationService
    {
        public function normalize(string $text): string
        {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = str_replace(["\r\n", "\r"], "\n", $text);
            $text = mb_strtolower($text, 'UTF-8');

            // Quitar URLs
            $text = preg_replace('/https?:\/\/\S+/iu', ' ', $text) ?? $text;
            $text = preg_replace('/www\.\S+/iu', ' ', $text) ?? $text;

            // Dejar letras, números y espacios; conservar acentos y ñ por Unicode
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;

            // Compactar espacios
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

            return trim($text);
        }
    }