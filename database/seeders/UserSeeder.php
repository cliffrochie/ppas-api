<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleRequester = Role::where('name', 'requester')->value('id');
        $roleProcurement = Role::where('name', 'procurement_officer')->value('id');
        $roleBudget = Role::where('name', 'budget_officer')->value('id');

        $orm = Office::where('code', 'ORM')->value('id');
        $bac = Office::where('code', 'BAC')->value('id');
        $as = Office::where('code', 'AS')->value('id');
        $fs = Office::where('code', 'FS')->value('id');
        $es = Office::where('code', 'ES')->value('id');
        $os = Office::where('code', 'OS')->value('id');

        $users = [
            // Procurement Officers (PPU / BAC Secretariat)
            [
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'last_name' => 'Dela Cruz',
                'extension_name' => null,
                'email' => 'procurement@nia-caraga.test',
                'role_id' => $roleProcurement,
                'office_id' => $bac,
            ],

            // Budget Officers (Budget Section)
            [
                'first_name' => 'Maria',
                'middle_name' => 'Reyes',
                'last_name' => 'Santos',
                'extension_name' => null,
                'email' => 'budget@nia-caraga.test',
                'role_id' => $roleBudget,
                'office_id' => $as,
            ],

            // Requesters (sample end-users from different offices)
            [
                'first_name' => 'Carlos',
                'middle_name' => 'Lopez',
                'last_name' => 'Reyes',
                'extension_name' => null,
                'email' => 'requester.ord@nia-caraga.test',
                'role_id' => $roleRequester,
                'office_id' => $fs,
            ],
            [
                'first_name' => 'Ana',
                'middle_name' => 'Cruz',
                'last_name' => 'Mendoza',
                'extension_name' => null,
                'email' => 'requester.ed@nia-caraga.test',
                'role_id' => $roleRequester,
                'office_id' => $es,
            ],
            [
                'first_name' => 'Ricardo',
                'middle_name' => 'Garcia',
                'last_name' => 'Lim',
                'extension_name' => null,
                'email' => 'requester.fd@nia-caraga.test',
                'role_id' => $roleRequester,
                'office_id' => $os,
            ],
            [
                'first_name' => 'Sofia',
                'middle_name' => 'Bautista',
                'last_name' => 'Torres',
                'extension_name' => null,
                'email' => 'requester.ict@nia-caraga.test',
                'role_id' => $roleRequester,
                'office_id' => $os,
            ],
        ];

        $defaults = [
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                array_merge($defaults, $user),
            );
        }
    }
}
