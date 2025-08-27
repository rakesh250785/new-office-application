<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'branch_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'branch_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderDetails()
    {
        return $this->hasManyThrough(
            OrderDetails::class,
            Order::class,
            'branch_id',
            'order_id',
            'id',
            'id'
        );
    }
}
