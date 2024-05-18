<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadFile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_extension',
        'file_size',
        'file_type',
        'file_path',
        'file_url'
    ];
}
