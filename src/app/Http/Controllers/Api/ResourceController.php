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

    if ($request->has('max_capacity')) {
        $query->where('capacity', '<=', $request->max_capacity);
    }

    // Фильтрация по локации
    if ($request->has('location')) {
        $query->where('location', 'like', "%{$request->location}%");
    }

    // Поиск по названию или локации
    if ($request->has('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    // Фильтрация по доступности
    if ($request->has('is_active')) {
        $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
    }

    // Сортировка
    $sortBy = $request->get('sort_by', 'name');
    $sortOrder = $request->get('sort_order', 'asc');
    
    // Разрешенные поля для сортировки
    $allowedSorts = ['name', 'capacity', 'type', 'created_at', 'updated_at'];
    if (in_array($sortBy, $allowedSorts)) {
        $query->orderBy($sortBy, $sortOrder);
    }

    // Пагинация
    $perPage = $request->get('per_page', 15);
    $perPage = min(max($perPage, 1), 100); // от 1 до 100

    return $query->paginate($perPage);
}

    /**
     * Создание ресурса (только админ)
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Access denied. Admins only.'], 403);
        }

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
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Access denied. Admins only.'], 403);
        }

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
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Access denied. Admins only.'], 403);
        }
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