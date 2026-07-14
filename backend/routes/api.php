<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/auth/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::get('/auth/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');

Route::get('/credits/transactions', [App\Http\Controllers\Api\CreditController::class, 'transactions'])->middleware('auth:sanctum');
Route::get('/credits/balance', [App\Http\Controllers\Api\CreditController::class, 'balance'])->middleware('auth:sanctum');

Route::post('/video/reference', [App\Http\Controllers\Api\VideoController::class, 'uploadReference']);
Route::delete('/video/reference', [App\Http\Controllers\Api\VideoController::class, 'deleteReference']);
Route::post('/kling/recommend', [App\Http\Controllers\Api\KlingController::class, 'recommend']);

Route::get('/', function () {
    return response()->json(['name' => 'AI短剧 API', 'version' => '2.0']);
});
