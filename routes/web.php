<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PhotoController;

Route::get('/', function () {
    return app(PhotoController::class)->index();
})->name('home');

Route::middleware('auth')->prefix('photos')->group(function () {
    Route::get('', [PhotoController::class, 'manage'])->name('dashboard');
    Route::post('', [PhotoController::class, 'store'])->name('photos.store');
    Route::delete('/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
