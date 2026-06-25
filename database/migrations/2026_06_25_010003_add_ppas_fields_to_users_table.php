<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete()
                ->after('password');
            $table->foreignId('office_id')
                ->nullable()
                ->constrained('offices')
                ->nullOnDelete()
                ->after('role_id');
            $table->boolean('is_active')->default(true)->index()->after('office_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('office_id');
            $table->dropColumn('is_active');
        });
    }
};
