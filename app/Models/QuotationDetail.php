<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{   
    protected $table = "quotation_details";
    protected $primaryKey = 'id';
    protected $fillable = [
        'quotation_id',
        'unique_quotaion_no',
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
        'total',
        'notes',
        'product_specification',
        'delivery_date_id',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
}
