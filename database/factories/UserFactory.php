<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'        => fake()->firstName(),
            'middle_name'       => fake()->lastName(),
            'last_name'         => fake()->lastName(),
            'extension_name'    => '',
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role_id'           => Role::where('name', 'requester')->value('id'),
            'office_id'         => null,
            'is_active'         => true,
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function requester(?int $officeId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id'   => Role::where('name', 'requester')->value('id'),
            'office_id' => $officeId,
        ]);
    }

    public function procurementOfficer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id'   => Role::where('name', 'procurement_officer')->value('id'),
            'office_id' => Office::where('code', 'PPU')->value('id'),
        ]);
    }

    public function budgetOfficer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id'   => Role::where('name', 'budget_officer')->value('id'),
            'office_id' => Office::where('code', 'BS')->value('id'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
