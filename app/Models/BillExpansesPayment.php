<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BillExpansesPayment extends Model
{
    protected $fillable = [
        'expanses_company_detail_id',
        'advance_payment',
        'advance_details',
        'uploaded_file',
        'totals',
        'user_id',
    ];

    protected $casts = [
        'uploaded_file' => 'array',
        'totals' => 'array',
    ];
}