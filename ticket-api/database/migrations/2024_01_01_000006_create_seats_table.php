<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectors')->cascadeOnDelete();
            $table->string('row_char', 5);
            $table->unsignedInteger('seat_number');
            $table->string('code')->unique();
            $table->boolean('is_reserved')->default(false);
            $table->boolean('is_available')->default(true);
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamps();

            $table->index(['sector_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
