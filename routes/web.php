<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::middleware('web')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    })->name('home');

    Route::get('/create', function () {
        return Inertia::render('public/CreateLink');
    })->name('links.create');
});

Route::middleware(['auth', 'verified'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
    });

Route::middleware(['auth', 'verified'])
    ->prefix('domains')
    ->name('domains.')
    ->group(function () {
        Route::get('/', function () {
            return Inertia::render('domains/Index');
        })->name('index');
    });

require __DIR__.'/settings.php';
