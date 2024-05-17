<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'country',
        'province',
        'city',
        'zip_code',
        'complete_address'
    ];
}
