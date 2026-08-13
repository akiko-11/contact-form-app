<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index'])
    ->name('contact.index');

Route::post('/contacts/confirm', [ContactController::class, 'confirm'])
    ->name('contact.confirm');

Route::post('/contacts', [ContactController::class, 'store'])
    ->name('contact.store');

Route::get('/thanks', [ContactController::class, 'thanks'])
    ->name('contact.thanks');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);

    Route::get('/contacts/export', [
        ContactController::class,
        'export',
    ]);

    Route::get('/admin/contacts/{contact}', [
        AdminController::class,
        'show',
    ]);

    Route::delete('/admin/contacts/{contact}', [
        AdminController::class,
        'destroy',
    ]);

    Route::post('/admin/tags', [
        TagController::class,
        'store',
    ]);

    Route::get('/admin/tags/{tag}/edit', [
        TagController::class,
        'edit',
    ]);

    Route::put('/admin/tags/{tag}', [
        TagController::class,
        'update',
    ]);

    Route::delete('/admin/tags/{tag}', [
        TagController::class,
        'destroy',
    ]);
});
