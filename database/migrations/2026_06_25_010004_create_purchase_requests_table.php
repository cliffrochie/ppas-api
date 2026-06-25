<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rf_number', 50)->unique()->nullable();
            $table->string('pr_number', 50)->unique()->nullable();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('requesting_office_id')->constrained('offices')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('purpose');
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'returned',
                'for_budget_approval',
                'disapproved',
                'budget_approved',
                'forwarded_to_ppu',
                'pr_prepared',
                'pr_approved',
                'rfq_prepared',
                'canvassing',
                'abstract_prepared',
                'bac_resolution_noa',
                'po_prepared',
                'completed',
            ])->default('draft')->index();
            $table->string('alobs_number', 100)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
