<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'hotel_id',
        'max_visitor',
        'room_sizes',
        'smoking_allowed',
        'facilities',
        'hotel_photos',
        'overnight_prices',
        'total_room',
        'total_booked'
    ];
}
