<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10);
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->integer('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['prefix', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
