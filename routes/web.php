<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\BingoController;
use App\Livewire\BingoCardPlayer;
use App\Livewire\BingoLobby;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/bingo/shared/{uuid}/{token}', [BingoController::class, 'showShared'])
    ->whereUuid('uuid')
    ->name('bingo.card.shared');

Route::middleware(['auth', 'verified', 'ensure.not.blocked'])->prefix('bingo')->name('bingo.')->group(function (): void {
    Route::get('/', BingoLobby::class)->name('index');
    Route::get('/play/{subject}', [BingoController::class, 'play'])->name('play');
    Route::get('/card/{uuid}', BingoCardPlayer::class)
        ->whereUuid('uuid')
        ->name('card');
});

Route::middleware('throttle:6,1')->group(function (): void {
    Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->name('auth.socialite.redirect');
    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->name('auth.socialite.callback');
});

require __DIR__.'/settings.php';
