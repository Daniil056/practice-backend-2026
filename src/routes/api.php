<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AnalyticsController;

// Публичные маршруты (аутентификация)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Защищённые маршруты (требуют авторизации)
// Защищённые маршруты (требуют авторизации)
Route::middleware('auth:sanctum')->group(function () {

    // Аутентификация
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Ресурсы
    Route::apiResource('resources', ResourceController::class);

    // Бронирования
    Route::get('/bookings/my', [BookingController::class, 'myBookings']);
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::apiResource('bookings', BookingController::class);

    // Аналитика (только авторизованные)
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('/analytics/resource-utilization', [AnalyticsController::class, 'resourceUtilization']);
    Route::get('/analytics/resources/{resource}/schedule', [AnalyticsController::class, 'resourceSchedule']);
    Route::get('/analytics/my-stats', [AnalyticsController::class, 'userStats']);
});