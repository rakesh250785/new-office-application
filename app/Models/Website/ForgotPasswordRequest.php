<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class ForgotPasswordRequest extends Model
{
    protected $table = 'forgot_password';

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'status',
        'requested_at',
        'used_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
