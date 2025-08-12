<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

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

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
