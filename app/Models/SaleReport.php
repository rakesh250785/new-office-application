<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReport extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d', // auto format as Y-m-d
        'qty'          => 'integer',    // ✅ integer, not double
        'amount'       => 'decimal:2',  // ✅ correct
    ];
}
