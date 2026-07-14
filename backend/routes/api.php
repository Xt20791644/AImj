<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\Api\KlingController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\StudioController;

// Public
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/kling/options', [KlingController::class, 'options']);
Route::get('/kling/defaults', [KlingController::class, 'defaults']);
Route::post('/kling/recommend', [KlingController::class, 'recommend']);
Route::post('/kling/validate', [KlingController::class, 'validateConfig']);

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
    Route::get('/users/{id}/works', [AdminController::class, 'userWorks']);
    Route::post('/users/{id}/password', [AdminController::class, 'resetPassword']);
    Route::get('/transactions', [AdminController::class, 'transactions']);
});

// Studio — 角色/剧集/分镜
Route::middleware('auth:sanctum')->prefix('studio')->group(function () {
    Route::get('/works/{workId}/characters', [StudioController::class, 'characters']);
    Route::post('/works/{workId}/characters', [StudioController::class, 'storeCharacter']);
    Route::put('/works/{workId}/characters/{id}', [StudioController::class, 'updateCharacter']);
    Route::delete('/works/{workId}/characters/{id}', [StudioController::class, 'deleteCharacter']);
    Route::get('/works/{workId}/episodes', [StudioController::class, 'episodes']);
    Route::post('/works/{workId}/episodes', [StudioController::class, 'storeEpisode']);
    Route::put('/works/{workId}/episodes/{id}', [StudioController::class, 'updateEpisode']);
    Route::get('/works/{workId}/episodes/{id}', [StudioController::class, 'showEpisode']);
    Route::get('/episodes/{episodeId}/storyboards', [StudioController::class, 'storyboards']);
    Route::post('/episodes/{episodeId}/storyboards', [StudioController::class, 'storeStoryboard']);
    Route::put('/episodes/{episodeId}/storyboards/{id}', [StudioController::class, 'updateStoryboard']);
    Route::delete('/episodes/{episodeId}/storyboards/{id}', [StudioController::class, 'deleteStoryboard']);
    Route::post('/recommend-model', [StudioController::class, 'recommendModel']);
    Route::post('/works/{workId}/compose', [StudioController::class, 'composeWork']);
});
