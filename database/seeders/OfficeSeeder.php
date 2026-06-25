<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        // Placeholder offices for NIA Caraga Regional Office.
        // Update names and codes to match the actual organizational structure.
        $offices = [
            ['name' => 'Office of the Regional Manager', 'code' => 'ORM'],
            ['name' => 'Bids and Awards Committee', 'code' => 'BAC'],
            ['name' => 'Administrative Section', 'code' => 'AS'],
            ['name' => 'Finance Section', 'code' => 'FS'],
            ['name' => 'Engineering Section', 'code' => 'ES'],
            ['name' => 'Operation Section', 'code' => 'OS'],
        ];

        foreach ($offices as $office) {
            Office::firstOrCreate(
                ['code' => $office['code']],
                ['name' => $office['name']],
            );
        }
    }
}
