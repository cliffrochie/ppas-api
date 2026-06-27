<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Flags requests with total_amount >= ₱50,000 for mandatory PhilGEPS posting.
            // Computed and stamped automatically on every save — never set by end-users directly.
            $table->boolean('requires_philgeps')->default(false)->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('requires_philgeps');
        });
    }
};
