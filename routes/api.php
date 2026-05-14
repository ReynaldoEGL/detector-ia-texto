<?php

use App\Http\Controllers\Api\TextClassificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Detector de Texto IA
|--------------------------------------------------------------------------
|
| POST /api/clasificar-texto   Analiza un texto y devuelve si es IA o Humano.
| GET  /api/health             Estado del servicio Laravel + Python.
| GET  /api/modelos            Modelos disponibles y configuración.
|
*/

// ── Análisis de texto ────────────────────────────────────────────────────────
Route::post('/clasificar-texto', [TextClassificationController::class, 'analyze'])
    ->middleware('throttle:60,1')
    ->name('api.clasificar');

// ── Utilidades ───────────────────────────────────────────────────────────────
Route::get('/health', [TextClassificationController::class, 'health'])
    ->name('api.health');

Route::get('/modelos', [TextClassificationController::class, 'models'])
    ->name('api.modelos');
