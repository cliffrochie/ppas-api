<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tin_number', 100)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('website')->nullable();
            $table->json('tags')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 50)->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_province')->nullable();
            $table->string('address_zip', 20)->nullable();
            $table->decimal('on_time_delivery_rate', 5, 2)->nullable();
            $table->decimal('defect_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
