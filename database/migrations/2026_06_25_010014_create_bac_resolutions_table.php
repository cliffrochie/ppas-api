<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bac_resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('resolution_number', 100);
            $table->foreignId('abstract_of_quotation_id')->unique()->constrained('abstracts_of_quotation')->restrictOnDelete();
            $table->foreignId('prepared_by_id')->constrained('users')->restrictOnDelete();
            $table->string('file_path', 500);
            $table->date('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bac_resolutions');
    }
};
