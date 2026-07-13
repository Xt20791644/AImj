<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\Api\KlingController;
use App\Http\Controllers\Admin\AdminController;

// Public
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/kling/options', [KlingController::class, 'options']);
Route::get('/kling/defaults', [KlingController::class, 'defaults']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/credits/balance', [CreditController::class, 'balance']);
    Route::get('/credits/transactions', [CreditController::class, 'transactions']);
    Route::get('/works', [WorkController::class, 'index']);
    Route::post('/works', [WorkController::class, 'store']);
    Route::get('/works/{id}', [WorkController::class, 'show']);
    Route::delete('/works/{id}', [WorkController::class, 'destroy']);
    Route::get('/works/{id}/timeline', [WorkController::class, 'timeline']);
    // 精细模式分步API
    Route::post('/works/{id}/script', [WorkController::class, 'confirmScript']);
    Route::post('/works/{id}/images', [WorkController::class, 'generateImages']);
    Route::post('/works/{id}/finalize', [WorkController::class, 'finalizeWork']);
});

// Admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users/{id}/recharge', [AdminController::class, 'recharge']);
    Route::delete('/users/{id}', [AdminController::class, 'destroy']);
    Route::get('/transactions', [AdminController::class, 'transactions']);
});
