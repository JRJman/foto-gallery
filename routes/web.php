<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PhotoController;

Route::get('/', function () {
    return app(PhotoController::class)->index();
})->name('home');

Route::post('/photos', [PhotoController::class, 'store'])->middleware('auth')->name('photos.store');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
