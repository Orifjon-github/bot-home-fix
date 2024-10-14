<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/{id}', [App\Http\Controllers\ExelController::class, 'index']);
Route::get('/new/{id}', [App\Http\Controllers\ExelexportController::class, 'index']);
