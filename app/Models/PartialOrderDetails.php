<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartialOrderDetails extends Model
{
    protected $fillable = [
        'partial_order_id',
        'order_id',
        'quotation_id',
        'unique_order_no',
        'unique_quotation_no',
        'unique_partial_order_no',
        'product_id',
        'principal_id',
        'part_no',
        'description',
        'hsn_code',
        'in_stock',
        'price',
        'discount',
        'net_price',
        'igst',
        'balance_quantity',
        'order_type',
        'quantity',
        'total',
        'status',
        'partial_order_status',
        'notes',
        'product_specification',
        'delivery_date_id',
        'deleted_at',
    ];

}

