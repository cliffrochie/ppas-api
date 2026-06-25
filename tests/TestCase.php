<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\RoleSeeder;
use Database\Seeders\OfficeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Seed required reference data (roles, offices) before each test.
     * Tests that need additional fixtures create them inline.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(OfficeSeeder::class);
    }
}
