<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkExpansesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'expanses_company_detail_id',
        'purpose',
        'purpose_order_no',
        'purchase_equipment',
        'purchase_hardware',
        'labor',
        'totals',
        'user_id',
    ];

    protected $casts = [
        'purchase_equipment' => 'array',
        'purchase_hardware' => 'array',
        'labor' => 'array',
        'totals' => 'array',
    ];
}
