<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Список всех бронирований
     */
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

        // Фильтрация по дате
        if ($request->has('date')) {
            $date = $request->date;
            $query->whereDate('start_time', '<=', $date)
                  ->whereDate('end_time', '>=', $date);
        }

        return $query->latest()->paginate(15);
    }

    /**
     * Создание бронирования
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'nullable|string|max:255',
        ]);

        $userId = auth()->id();
        $resourceId = $validated['resource_id'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        try {
            $booking = DB::transaction(function () use ($userId, $resourceId, $startTime, $endTime, $validated) {
                
                $conflict = Booking::where('resource_id', $resourceId)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    })
                    ->lockForUpdate()
                    ->first();

                if ($conflict) {
                    throw new \Exception('Time slot is already booked', 409);
                }

                return Booking::create([
                    'user_id' => $userId,
                    'resource_id' => $resourceId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'confirmed',
                    'purpose' => $validated['purpose'] ?? null,
                ]);
            });

            return response()->json([
                'message' => 'Booking created successfully',
                'data' => $booking->load('resource', 'user')
            ], 201);

        } catch (\Exception $e) {
            if ($e->getCode() === 409 || str_contains($e->getMessage(), 'already booked')) {
                return response()->json(['error' => 'Time slot is already booked'], 409);
            }
            return response()->json(['error' => 'Failed to create booking'], 500);
        }
    }

    /**
     * Просмотр конкретного бронирования
     */
    public function show(Booking $booking)
    {
        // Проверка доступа
        if ($booking->user_id !== request()->user()->id && request()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($booking->load(['user', 'resource']));
    }

    /**
     * Подтверждение бронирования (только админ)
     */
    public function confirm(Booking $booking)
    {
        if (request()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Можно подтвердить только pending бронирование
        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending bookings can be confirmed'
            ], 422);
        }

        $booking->update(['status' => 'confirmed']);

        return response()->json([
            'message' => 'Booking confirmed',
            'booking' => $booking,
        ]);
    }

    /**
     * Отмена бронирования
     */
    public function cancel(Booking $booking)
    {
        // Пользователь может отменить только своё, админ - любое
        if ($booking->user_id !== request()->user()->id && request()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Нельзя отменить уже подтверждённое или завершённое бронирование
        if (in_array($booking->status, ['confirmed', 'completed'])) {
            return response()->json([
                'message' => 'Cannot cancel confirmed or completed booking'
            ], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Booking cancelled',
            'booking' => $booking,
        ]);
    }

    /**
     * Мои бронирования
     */
    public function myBookings(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with('resource')
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    /**
     * Обновление (не используется)
     */
    public function update(Request $request, Booking $booking)
    {
        return response()->json(['message' => 'Use cancel/confirm endpoints instead'], 405);
    }

    /**
     * Удаление (не используется)
     */
    public function destroy(Booking $booking)
    {
        return response()->json(['message' => 'Use cancel endpoint instead'], 405);
    }
}