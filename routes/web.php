<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index'])
    ->name('contact.index');
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])
    ->name('contact.confirm');
