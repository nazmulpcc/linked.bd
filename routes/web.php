<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Domains\DomainController;
use App\Http\Controllers\Links\LinkController;
use App\Http\Controllers\Links\LinkManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::middleware('web')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    })->name('home');

    Route::get('/create', [LinkController::class, 'create'])->name('links.create');
    Route::post('/links', [LinkController::class, 'store'])->name('links.store');
    Route::get('/links/success/{token}', [LinkController::class, 'success'])
        ->name('links.success');

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

        Route::get('/links', [LinkManagementController::class, 'index'])->name('links.index');
        Route::delete('/links/{link}', [LinkManagementController::class, 'destroy'])
            ->name('links.destroy');
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
