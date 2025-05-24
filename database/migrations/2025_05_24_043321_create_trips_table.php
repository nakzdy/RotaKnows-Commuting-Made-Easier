<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('origin_address');
            $table->string('destination_address');
            $table->string('origin_lat')->nullable();
            $table->string('origin_lon')->nullable();
            $table->string('destination_lat')->nullable();
            $table->string('destination_lon')->nullable();
            $table->double('distance_km')->nullable();
            $table->integer('travel_time_minutes')->nullable();
            $table->double('expected_fare');
            $table->json('weather_data')->nullable();
            $table->json('news_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};