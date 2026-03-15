<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Общая статистика
     */
    public function overview(Request $request)
    {
        $totalResources = Resource::count();
        $activeResources = Resource::where('is_active', true)->count();
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();

        return response()->json([
            'total_resources' => $totalResources,
            'active_resources' => $activeResources,
            'total_bookings' => $totalBookings,
            'pending_bookings' => $pendingBookings,
            'confirmed_bookings' => $confirmedBookings,
            'cancelled_bookings' => $cancelledBookings,
        ]);
    }

    /**
     * Загруженность ресурсов
     */
    public function resourceUtilization(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $resources = Resource::withCount([
            'bookings as total_bookings' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate])
                      ->where('status', '!=', 'cancelled');
            }
        ])
        ->where('is_active', true)
        ->orderBy('total_bookings', 'desc')
        ->get();

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'resources' => $resources,
        ]);
    }

    /**
     * Расписание ресурса на дату
     */
    public function resourceSchedule(Request $request, Resource $resource)
    {
        $date = $request->get('date', now()->toDateString());

        $bookings = $resource->bookings()
            ->whereDate('start_time', '<=', $date)
            ->whereDate('end_time', '>=', $date)
            ->where('status', '!=', 'cancelled')
            ->with('user')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'resource' => $resource,
            'date' => $date,
            'bookings' => $bookings,
        ]);
    }

    /**
     * Статистика пользователя
     */
    public function userStats(Request $request)
    {
        $user = $request->user();

        $totalBookings = Booking::where('user_id', $user->id)->count();
        $pendingBookings = Booking::where('user_id', $user->id)
            ->where('status', 'pending')->count();
        $confirmedBookings = Booking::where('user_id', $user->id)
            ->where('status', 'confirmed')->count();
        $cancelledBookings = Booking::where('user_id', $user->id)
            ->where('status', 'cancelled')->count();

        $upcomingBookings = Booking::where('user_id', $user->id)
            ->where('start_time', '>', now())
            ->where('status', '!=', 'cancelled')
            ->count();

        return response()->json([
            'user' => $user,
            'total_bookings' => $totalBookings,
            'pending_bookings' => $pendingBookings,
            'confirmed_bookings' => $confirmedBookings,
            'cancelled_bookings' => $cancelledBookings,
            'upcoming_bookings' => $upcomingBookings,
        ]);
    }
}