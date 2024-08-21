<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/start', [TelegramBotController::class, 'start']);
Route::get('/ping', [MainController::class, 'ping']);

Route::controller(MainController::class)
    ->middleware('auth')
    ->group(function () {
        Route::prefix('chat')->group(function () {
            Route::get('open', 'openChats');
            Route::get('detail/{id}', 'chatDetail');
            Route::post('admin', 'adminChats');
        });
    });
