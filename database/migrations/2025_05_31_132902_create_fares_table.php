<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fares', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('vehicle_type')->nullable(); // e.g., 'jeep', 'tricycle'
            $table->decimal('base_fare', 8, 2); // e.g., 10.00
            $table->decimal('distance_rate_per_km', 8, 2)->nullable(); // e.g., 2.50 per km
            $table->string('origin_address')->nullable(); // Optional: if fares are origin-specific
            $table->string('destination_address')->nullable(); // Optional: if fares are destination-specific
            $table->timestamps(); // created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fares');
    }
};