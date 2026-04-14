<?php

use App\Http\Controllers\LinkShareController;
use Illuminate\Support\Facades\Route;

// Route de tracking pour les liens partagés
Route::get('/share/{token}', [LinkShareController::class, 'redirect'])
    ->name('links.share.redirect');
