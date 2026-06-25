<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only audit log — no updated_at column by design.
        Schema::create('pr_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->index();
            $table->text('remarks')->nullable();
            $table->string('alobs_number', 100)->nullable();
            $table->timestamp('acted_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_status_histories');
    }
};
