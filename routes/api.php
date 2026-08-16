<?php

use Illuminate\Support\Facades\Route;
use Modules\MCH\Http\Controllers\MCHController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('mches', MCHController::class)->names('mch');
});
