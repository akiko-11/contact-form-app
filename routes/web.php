<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index'])
    ->name('contact.index');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);

    Route::get('/admin/contacts/{contact}', [
        AdminController::class,
        'show',
    ]);

    Route::delete('/admin/contacts/{contact}', [
        AdminController::class,
        'destroy',
    ]);
});
