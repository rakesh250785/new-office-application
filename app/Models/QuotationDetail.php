<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{   
    protected $table = "quotation_details";
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id', //
        'order_type', //
        'quotation_id',
        'unique_quotation_no',
        'unique_order_no', //
        'product_id',
        'principal_id',
        'part_no',
        'description',
        'hsn_code',
        'quantity',
        'in_stock',
        'price',
        'discount',
        'net_price',
        'igst',
        'balance_quantity',
        'total',
        'notes',
        'status', //
        'partial_order_status', //
        'product_specification',
        'delivery_date_id',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function principal()
    {
        return $this->belongsTo(Principal::class, 'principal_id');
    }
}
          