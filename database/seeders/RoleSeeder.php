<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'requester', 'description' => 'End-user / requesting unit personnel who submits procurement requests'],
            ['name' => 'procurement_officer', 'description' => 'Property and Procurement Unit (PPU) / BAC Secretariat'],
            ['name' => 'budget_officer', 'description' => 'Budget Section — validates fund availability and encodes ALOBS'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']],
            );
        }
    }
}
