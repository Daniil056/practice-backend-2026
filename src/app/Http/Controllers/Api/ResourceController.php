<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    /**
     * Список всех ресурсов
     */
    public function index(Request $request)
    {
        $query = Resource::query();

        // Фильтрация по типу
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Фильтрация по вместимости
        if ($request->has('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        // Поиск по названию или локации
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Только активные ресурсы
        $query->where('is_active', true);

        return $query->paginate(15);
    }

    /**
     * Создание ресурса (только админ)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:meeting_room,desk,office',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $resource = Resource::create($request->all());

        return response()->json([
            'message' => 'Resource created successfully',
            'resource' => $resource,
        ], 201);
    }

    /**
     * Просмотр конкретного ресурса
     */
    public function show(Resource $resource)
    {
        return response()->json($resource->load('bookings'));
    }

    /**
     * Обновление ресурса (только админ)
     */
    public function update(Request $request, Resource $resource)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:meeting_room,desk,office',
            'capacity' => 'sometimes|required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $resource->update($request->all());

        return response()->json([
            'message' => 'Resource updated successfully',
            'resource' => $resource,
        ]);
    }

    /**
     * Удаление ресурса (только админ)
     */
    public function destroy(Resource $resource)
    {
        // Проверка, есть ли активные бронирования
        $hasBookings = $resource->bookings()
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasBookings) {
            return response()->json([
                'message' => 'Cannot delete resource with active bookings'
            ], 422);
        }

        $resource->delete();

        return response()->json([
            'message' => 'Resource deleted successfully',
        ]);
    }
}