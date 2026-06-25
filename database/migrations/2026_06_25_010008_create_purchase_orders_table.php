<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('purchase_request_id')->unique()->constrained('purchase_requests')->restrictOnDelete();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->string('supplier_name')->nullable();
            $table->text('supplier_address')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->text('payment_terms')->nullable();
            $table->date('delivery_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'for_signature', 'signed', 'acknowledged', 'completed'])
                ->default('draft')
                ->index();
            $table->string('signed_po_path', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
