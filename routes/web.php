<?php

use App\Http\Controllers\Api\CauseController;
use App\Http\Controllers\Api\PledgeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ============================================
// ROUTES API
// ============================================
Route::prefix('api')->group(function () {

    // Routes pour les Causes (CRUD)
    Route::apiResource('causes', CauseController::class);

    // Route pour les Pledges (avec idempotence)
    Route::post('causes/{cause}/pledges', [PledgeController::class, 'store'])
        ->middleware('idempotency')
        ->name('pledges.store');

    // Route publique - Liste des causes
    Route::get('public/causes', [CauseController::class, 'index']);

    // Route pour toggle featured (admin)
    Route::patch('causes/{cause}/toggle-featured', [CauseController::class, 'toggleFeatured'])
        ->name('causes.toggle-featured');
});
