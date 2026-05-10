<?php

use App\Http\Controllers\Api\TextClassificationController;
use Illuminate\Support\Facades\Route;

Route::post('/clasificar-texto', [TextClassificationController::class, 'analyze'])
    ->middleware('throttle:60,1');

?>