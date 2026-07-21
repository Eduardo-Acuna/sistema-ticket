<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Conciertos', 'icon' => 'music', 'color' => '#667eea'],
            ['name' => 'Deportes', 'icon' => 'trophy', 'color' => '#f59e0b'],
            ['name' => 'Teatro', 'icon' => 'theater-masks', 'color' => '#764ba2'],
            ['name' => 'Festivales', 'icon' => 'star', 'color' => '#ef4444'],
            ['name' => 'Conferencias', 'icon' => 'briefcase', 'color' => '#10b981'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
