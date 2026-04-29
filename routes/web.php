<?php

use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/f/{site}/{slug}', [FormController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('form.show');
