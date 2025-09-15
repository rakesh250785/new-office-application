<?php

namespace App\Models;
use Illuminate\Notifications\DatabaseNotification as BaseNotification;

class Notification extends BaseNotification
{
    protected $table = "notifications";

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function notifiableUser()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}
