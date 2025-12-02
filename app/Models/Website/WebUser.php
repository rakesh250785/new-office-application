<?php

namespace App\Models\Website;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WebUser extends Model
{
    protected $table = 'web_users';

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'mobile',
        'gender',
        'email_verified_at',
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
