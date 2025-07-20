<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'branch_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'date',
        ];
    }
}
