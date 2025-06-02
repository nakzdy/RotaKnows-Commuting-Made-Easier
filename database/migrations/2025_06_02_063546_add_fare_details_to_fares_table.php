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
        Schema::table('fares', function (Blueprint $table) {
            // Add the new columns
            $table->decimal('distance_km', 8, 2)->nullable()->after('destination_address');
            $table->integer('travel_time_minutes')->nullable()->after('distance_km');
            $table->decimal('expected_fare', 10, 2)->nullable()->after('travel_time_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fares', function (Blueprint $table) {
            // Drop the columns if rolling back
            $table->dropColumn(['distance_km', 'travel_time_minutes', 'expected_fare']);
        });
    }
};