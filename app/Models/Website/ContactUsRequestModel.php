<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ContactUsRequestModel extends Model
{
    protected $table = 'contact_us_requests';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
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
