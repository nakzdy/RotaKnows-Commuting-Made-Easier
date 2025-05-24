<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_address',
        'destination_address',
        'distance_km',
        'travel_time_minutes',
        'expected_fare',
        'weather_data',
        'news_data',
        'nearby_places_data',
    ];

    protected $casts = [
        'weather_data' => 'array',
        'news_data' => 'array',
        'nearby_places_data' => 'array',
    ];
}