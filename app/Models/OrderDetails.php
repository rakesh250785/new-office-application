<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'quotation_id',
        'unique_quotation_no',
        'unique_order_no',
        'product_id',
        'principal_id',
        'part_no',
        'description',
        'principal',
        'heading',
        'specification',
        'notes',
        'product_specification',
        'hsn_code',
        'quantity',
        'in_stock',
        'price',
        'discount',
        'net_price',
        'igst',
        'balance_quantity',
        'order_type',
        'order_quantity',
        'total',
        'status',
        'partial_order_status',
        'notes',
        'product_specification',
        'delivery_date_id',
        'deleted_at',
        'created_at',
        'updated_at',
        'user_id',
    ];

    public function principal()
    {
        return $this->belongsTo(Principal::class, 'principal_id');
    }

    public function uom()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
