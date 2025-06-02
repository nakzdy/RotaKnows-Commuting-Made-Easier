<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fare extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'origin_address',
        'destination_address',
        'vehicle_type',
        'distance_km',          
        'travel_time_minutes',  
        'expected_fare',        
        'base_fare',
        'distance_rate_per_km',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'distance_km' => 'float',
        'expected_fare' => 'float',
        'base_fare' => 'float',
        'distance_rate_per_km' => 'float',
        'travel_time_minutes' => 'integer',
    ];
}