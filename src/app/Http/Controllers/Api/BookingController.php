<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // Список всех бронирований (или своих)
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'resource']);

        // Если не админ, показываем только свои бронирования
        if ($request->user()->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтрация по ресурсу
        if ($request->has('resource_id')) {
            $query->where('resource_id', $request->resource_id);
        }

        return $query->latest()->paginate(15);
    }

    // Создание бронирования
    public function store(Request $request)
    {
        $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'nullable|string|max:500',
        ]);

        // Проверка на пересечение времени
        $hasConflict = Booking::where('resource_id', $request->resource_id)
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                      });
            })->exists();

        if ($hasConflict) {
            return response()->json([
                'message' => 'This resource is already booked for the selected time period',
            ], 422);
        }

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'resource_id' => $request->resource_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'pending',
            'purpose' => $request->purpose,
        ]);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking->load(['user', 'resource']),
        ], 201);
    }

    // Просмотр конкретного бронирования
    public function show(Booking $booking)
    {
        // Проверка доступа
        if ($booking->user_id !== request()->user()->id && request()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($booking->load(['user', 'resource']));
    }

    // Подтверждение бронирования (админ)
    public function confirm(Booking $booking)
    {
        if (request()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $booking->update(['status' => 'confirmed']);

        return response()->json([
            'message' => 'Booking confirmed',
            'booking' => $booking,
        ]);
    }

    // Отмена бронирования
    public function cancel(Booking $booking)
    {
        // Пользователь может отменить только своё, админ - любое
        if ($booking->user_id !== request()->user()->id && request()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Booking cancelled',
            'booking' => $booking,
        ]);
    }

    // Мои бронирования
    public function myBookings(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with('resource')
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    // Обновление и удаление не нужны для бронирований (только отмена)
    public function update(Request $request, Booking $booking)
    {
        return response()->json(['message' => 'Use cancel endpoint instead'], 405);
    }

    public function destroy(Booking $booking)
    {
        return response()->json(['message' => 'Use cancel endpoint instead'], 405);
    }
}