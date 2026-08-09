<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index'])
    ->name('contact.index');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
