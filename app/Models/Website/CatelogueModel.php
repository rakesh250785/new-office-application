<?php

namespace App\Models\Website;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CatelogueModel extends Model
{
    protected $table = 'catelogue';

    protected $fillable = [
        'name',
        'file',
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
