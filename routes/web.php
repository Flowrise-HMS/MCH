<?php

use Illuminate\Support\Facades\Route;
use Modules\MCH\Http\Controllers\MCHController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('mches', MCHController::class)->names('mch');
});
