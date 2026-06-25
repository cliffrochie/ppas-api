<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices_of_award', function (Blueprint $table) {
            $table->id();
            $table->string('noa_number', 100);
            $table->foreignId('bac_resolution_id')->unique()->constrained('bac_resolutions')->restrictOnDelete();
            $table->string('awarded_supplier');
            $table->decimal('awarded_amount', 15, 2);
            $table->string('file_path', 500);
            $table->date('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices_of_award');
    }
};
