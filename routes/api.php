<?php

use App\Http\Controllers\Api\BulkImportsController;
use App\Http\Controllers\Api\DomainsController;
use App\Http\Controllers\Api\LinkQrController;
use App\Http\Controllers\Api\LinksController;
use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/me', MeController::class)->name('api.me');

        Route::prefix('links')
            ->name('api.links.')
            ->group(function (): void {
                Route::middleware('abilities:links:read')->group(function (): void {
                    Route::get('/', [LinksController::class, 'index'])->name('index');
                    Route::get('/{link}', [LinksController::class, 'show'])->name('show');
                    Route::get('/{link}/qr', [LinkQrController::class, 'show'])->name('qr.show');
                });

                Route::middleware('abilities:links:write')->group(function (): void {
                    Route::post('/', [LinksController::class, 'store'])->name('store');
                    Route::delete('/{link}', [LinksController::class, 'destroy'])->name('destroy');
                });
            });

        Route::prefix('domains')
            ->name('api.domains.')
            ->group(function (): void {
                Route::middleware('abilities:domains:read')->group(function (): void {
                    Route::get('/', [DomainsController::class, 'index'])->name('index');
                    Route::get('/{domain}', [DomainsController::class, 'show'])->name('show');
                });

                Route::middleware('abilities:domains:write')->group(function (): void {
                    Route::post('/', [DomainsController::class, 'store'])->name('store');
                    Route::delete('/{domain}', [DomainsController::class, 'destroy'])->name('destroy');
                    Route::post('/{domain}/verify', [DomainsController::class, 'verify'])->name('verify');
                });
            });

        Route::prefix('bulk-imports')
            ->name('api.bulk-imports.')
            ->group(function (): void {
                Route::middleware('abilities:bulk:read')->group(function (): void {
                    Route::get('/{job}', [BulkImportsController::class, 'show'])->name('show');
                    Route::get('/{job}/items', [BulkImportsController::class, 'items'])->name('items');
                });

                Route::middleware('abilities:bulk:write')->group(function (): void {
                    Route::post('/', [BulkImportsController::class, 'store'])->name('store');
                });
            });
    });
