<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationType extends Model
{
    protected $fillable = [
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
