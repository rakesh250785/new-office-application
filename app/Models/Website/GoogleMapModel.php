<?php

namespace App\Models\Website;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class GoogleMapModel extends Model
{
    protected $table = 'google_map';

    protected $fillable = [
        'content',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
