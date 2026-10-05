<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/transactions/chart', [TransactionController::class, 'chart']);
    Route::get('/transactions/export', [TransactionController::class, 'export']);
    Route::post('/ai/ask', [AiAssistantController::class, 'ask']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
});