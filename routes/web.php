<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Domains\DomainController;
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

    Route::get('/oauth/google', [GoogleOAuthController::class, 'redirect'])
        ->name('oauth.google.redirect');
    Route::get('/oauth/google/callback', [GoogleOAuthController::class, 'callback'])
        ->name('oauth.google.callback');
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
        Route::get('/', [DomainController::class, 'index'])->name('index');
        Route::post('/', [DomainController::class, 'store'])->name('store');
        Route::post('/{domain}/verify', [DomainController::class, 'verify'])->name('verify');
        Route::post('/{domain}/disable', [DomainController::class, 'disable'])->name('disable');
        Route::delete('/{domain}', [DomainController::class, 'destroy'])->name('destroy');
    });

require __DIR__.'/settings.php';
