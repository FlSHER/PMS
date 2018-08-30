<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointCalculateLog extends Model
{
    protected $casts = [
        'data' => 'array',
    ];
}
