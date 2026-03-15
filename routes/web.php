<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\SupportBotController;
use Telegram\Bot\Api;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
// Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

Route::post('/support-webhook', [SupportBotController::class, 'handleSupport']);
// Route::post('/webhook', [TelegramController::class, 'webhook']);
Route::get('/game', [TelegramController::class, 'index'])->name('game.page');

Route::get('/game-2', [TelegramController::class, 'game_2'])->name('game-2.page');
Route::get('get_user_balance/{id}',[TelegramController::class, 'get_user_balance'])->name('get-user-balance');
Route::post('put_user_balance',[TelegramController::class, 'put_user_balance'])->name('put-user-balance');
// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/test', function () {
    return 'test';
});

