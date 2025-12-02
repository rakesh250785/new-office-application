<?php

namespace App\Models\Website;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class NewsLetterModel extends Model
{
    protected $table = 'news_letter';

    protected $fillable = [
        'email',
        'status',
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
