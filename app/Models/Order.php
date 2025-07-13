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
        return $this->belongsTo(Quotation::class, 'unique_quotation_id', 'id');
    }
    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id', 'id');
    }
    public function pendingQuotation()
    {
        return $this->hasOne(PendingQuotation::class, 'order_no', 'unique_order_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    public function branchAddress()
    {
        return $this->belongsTo(QuotationFormat::class, 'quotation_format_id', 'id')
            ->whereNull('deleted_at');
    }

}
