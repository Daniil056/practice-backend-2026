<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // Поля, которые можно заполнять массово
    protected $fillable = [
        'user_id',
        'resource_id',
        'start_time',
        'end_time',
        'status',
        'purpose',
    ];

    // Преобразование типов
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // Связь с пользователем
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Связь с ресурсом
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}