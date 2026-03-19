<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'branch_id',
        'user_id',
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

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
