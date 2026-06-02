<?php

use App\Http\Controllers\CryptoController;
use App\Http\Controllers\WatchlistController;

// Legacy endpoints
Route::get('/crypto/listings', [CryptoController::class, 'listings']);
Route::get('/crypto/global',   [CryptoController::class, 'global']);

// Watchlist (portfolio)
Route::get('/watchlist',                    [WatchlistController::class, 'index']);
Route::post('/watchlist',                   [WatchlistController::class, 'store']);
Route::delete('/watchlist/{id}',            [WatchlistController::class, 'destroy']);
Route::get('/watchlist/{id}/history',       [WatchlistController::class, 'history']);
Route::post('/watchlist/snapshot',          [WatchlistController::class, 'snapshot']);

// Search
Route::get('/search',                       [WatchlistController::class, 'search']);
