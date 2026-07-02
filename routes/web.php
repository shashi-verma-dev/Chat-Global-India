<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat Page
|--------------------------------------------------------------------------
*/

Route::get('/', [ChatController::class, 'index'])
    ->name('chat.index');

/*
|--------------------------------------------------------------------------
| Heartbeat — keeps presence tracker alive
|--------------------------------------------------------------------------
*/

Route::post('/heartbeat', [ChatController::class, 'heartbeat'])
    ->name('heartbeat');

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

Route::post('/messages', [MessageController::class, 'store'])
    ->name('messages.store');

/*
|--------------------------------------------------------------------------
| Likes
|--------------------------------------------------------------------------
*/

Route::post('/messages/{message}/like', [MessageLikeController::class, 'store'])
    ->name('messages.like');

/*
|--------------------------------------------------------------------------
| Admin Routes (code-protected, no session/auth required)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {

    Route::post('/chat/clear', [AdminController::class, 'clearChat'])
        ->name('admin.chat.clear');

    Route::post('/announcement', [AdminController::class, 'announcement'])
        ->name('admin.announcement');

});