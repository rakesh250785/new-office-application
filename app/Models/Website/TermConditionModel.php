<?php

namespace App\Models\Website;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TermConditionModel extends Model
{
    
    protected $table = 'term_condition';

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
