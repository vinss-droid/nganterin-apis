<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'service_rating',
        'cleanliness_rating',
        'value_for_money_rating',
        'location_rating',
        'cozy_rating',
        'comment',
    ];
}
