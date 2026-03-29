<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Resource;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Получить отзывы для ресурса + средний рейтинг
     */
    public function index($resourceId)
    {
        $resource = Resource::with(['reviews.user'])->findOrFail($resourceId);
        
        return response()->json([
            'resource_id' => $resource->id,
            'resource_name' => $resource->name,
            'average_rating' => round($resource->reviews->avg('rating') ?? 0, 2),
            'total_reviews' => $resource->reviews->count(),
            'reviews' => $resource->reviews->map(fn($r) => [
                'id' => $r->id,
                'user_name' => $r->user->name ?? 'Anonymous',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at?->toISOString(),
            ])
        ]);
    }

    /**
     * Создать или обновить отзыв (пользователь может иметь только один отзыв на ресурс)
     */
    public function store(Request $request, $resourceId)
    {
        // Проверка: существует ли ресурс
        $resource = Resource::findOrFail($resourceId);
        
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Обновляем или создаём отзыв
        $review = Review::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'resource_id' => $resourceId,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Review saved successfully',
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'average_rating' => round($resource->reviews()->avg('rating') ?? 0, 2),
            ]
        ], 200);
    }

    /**
     * Удалить свой отзыв
     */
    public function destroy($resourceId, $reviewId)
    {
        $review = Review::where('id', $reviewId)
            ->where('user_id', auth()->id())
            ->where('resource_id', $resourceId)
            ->firstOrFail();
            
        $review->delete();
        
        return response()->json(['message' => 'Review deleted successfully']);
    }
}