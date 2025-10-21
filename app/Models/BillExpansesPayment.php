<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BillExpansesPayment extends Model
{
    protected $fillable = [
        'expanses_company_detail_id',
        'advance_payment',
        'advance_details',
        'upload_file',
        'totals',
    ];

    protected $casts = [
        'upload_file' => 'array',
        'totals' => 'array',
    ];
}