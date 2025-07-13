<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $fillable = [];
    public function partialOrders()
    {
        return $this->hasMany(PartialOrder::class, 'partial_order_id', 'id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'cusomer_id');
    }
    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }
    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'qoute_unique_id', 'id');
    }
    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id', 'id');
    }
}
