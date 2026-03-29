<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ReviewController;

// ==================== ПУБЛИЧНЫЕ МАРШРУТЫ ====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ==================== ЗАЩИЩЁННЫЕ МАРШРУТЫ ====================
Route::middleware('auth:sanctum')->group(function () {

    // 🔐 Аутентификация
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // 📦 Ресурсы
    // Чтение — для всех авторизованных
    Route::get('/resources', [ResourceController::class, 'index']);
    Route::get('/resources/{resource}', [ResourceController::class, 'show']);
    
    // Создание/изменение/удаление — ТОЛЬКО АДМИНАМ
    Route::middleware('isAdmin')->group(function () {
        Route::post('/resources', [ResourceController::class, 'store']);
        Route::put('/resources/{resource}', [ResourceController::class, 'update']);
        Route::delete('/resources/{resource}', [ResourceController::class, 'destroy']);
    });

    // 📅 Бронирования
    Route::get('/bookings/my', [BookingController::class, 'myBookings']);
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    
    // Основной CRUD бронирований
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::put('/bookings/{booking}', [BookingController::class, 'update']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);

    // ⭐ ОТЗЫВЫ И РЕЙТИНГ (Этап 4 — обязательно!)
    Route::get('/resources/{resource}/reviews', [ReviewController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/resources/{resource}/reviews', [ReviewController::class, 'store']);
        Route::delete('/resources/{resource}/reviews/{review}', [ReviewController::class, 'destroy']);
    });

    // 📊 Аналитика
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('/analytics/resource-utilization', [AnalyticsController::class, 'resourceUtilization']);
    Route::get('/analytics/resources/{resource}/schedule', [AnalyticsController::class, 'resourceSchedule']);
    Route::get('/analytics/my-stats', [AnalyticsController::class, 'userStats']);
});