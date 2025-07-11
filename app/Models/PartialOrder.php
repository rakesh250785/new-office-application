<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartialOrder extends Model
{
    protected $table = 'partial_orders';
    protected $primaryKey = 'id';
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'partial_order_id', 'id');
    }
}
