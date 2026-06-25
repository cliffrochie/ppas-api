<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'ICT', 'description' => 'Information and Communications Technology supplies and equipment'],
            ['name' => 'General Services', 'description' => 'General services and maintenance supplies'],
            ['name' => 'Office', 'description' => 'Office supplies and materials'],
            ['name' => 'Utility', 'description' => 'Utility-related supplies and services'],
            ['name' => 'Vehicle', 'description' => 'Vehicle parts, accessories, and services'],
            ['name' => 'Others', 'description' => 'Other procurement items not covered by existing categories'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description'], 'is_active' => true],
            );
        }
    }
}
