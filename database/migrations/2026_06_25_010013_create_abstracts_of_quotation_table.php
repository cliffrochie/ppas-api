<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abstracts_of_quotation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->unique()->constrained('rfqs')->restrictOnDelete();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->string('recommended_supplier')->nullable();
            $table->decimal('recommended_amount', 15, 2)->nullable();
            $table->enum('status', ['draft', 'approved'])->default('draft')->index();
            $table->string('file_path', 500)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abstracts_of_quotation');
    }
};
