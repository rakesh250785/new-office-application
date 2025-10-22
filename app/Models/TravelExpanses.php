<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelExpanses extends Model
{
    use HasFactory;

    protected $fillable = [
        'expanses_company_detail_id',
        'purpose',
        'legs',
        'accompanying',
        'food',
        'hotel',
        'purchase_equipment',
        'purchase_hardware',
        'labor',
        'other_expenses',
        'totals',
        'user_id',
    ];

    protected $casts = [
        'legs' => 'array',
        'accompanying' => 'array',
        'food' => 'array',
        'hotel' => 'array',
        'purchase_equipment' => 'array',
        'purchase_hardware' => 'array',
        'labor' => 'array',
        'other_expenses' => 'array',
        'totals' => 'array',
    ];
}
