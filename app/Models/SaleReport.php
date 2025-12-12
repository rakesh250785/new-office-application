<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReport extends Model
{
    use HasFactory;

    protected $table = 'performance_reports';

    protected $fillable = [
        'qtr',
        'month',
        'fy_year',
        'invoice',
        'invoice_date',
        'order_no',
        'customer_name',
        'branch',
        'description',
        'part_no',
        'category',
        'principal_name',
        'authorised',
        'qty',
        'amount',
        'status'
    ];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d', 
        'qty'          => 'integer',    
        'amount'       => 'decimal:2',  
    ];
    
}
