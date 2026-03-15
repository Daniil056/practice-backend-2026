<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            [
                'name' => 'Переговорная А',
                'type' => 'meeting_room',
                'capacity' => 10,
                'location' => '1 этаж, кабинет 101',
                'features' => ['projector', 'whiteboard', 'video_conference'],
                'is_active' => true,
            ],
            [
                'name' => 'Переговорная Б',
                'type' => 'meeting_room',
                'capacity' => 6,
                'location' => '2 этаж, кабинет 205',
                'features' => ['tv', 'whiteboard'],
                'is_active' => true,
            ],
            [
                'name' => 'Рабочее место 1',
                'type' => 'desk',
                'capacity' => 1,
                'location' => 'Open space, зона A',
                'features' => ['monitor', 'ergonomic_chair'],
                'is_active' => true,
            ],
            [
                'name' => 'Рабочее место 2',
                'type' => 'desk',
                'capacity' => 1,
                'location' => 'Open space, зона B',
                'features' => ['monitor'],
                'is_active' => true,
            ],
            [
                'name' => 'Офис менеджера',
                'type' => 'office',
                'capacity' => 1,
                'location' => '3 этаж, кабинет 301',
                'features' => ['private', 'window'],
                'is_active' => true,
            ],
        ];

        foreach ($resources as $resource) {
            Resource::create($resource);
        }
    }
}