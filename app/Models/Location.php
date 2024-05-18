<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country',
        'state',
        'city',
        'zip_code',
        'complete_address',
        'gmaps'
    ];
}
