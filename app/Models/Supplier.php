<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'product_id',
        'principal_id',
        'source_id',
        'currency_id',
        'rate_fc',
        'profit',
        'factor_fc',
        'total_cost',
        'discount',
        'net_price',
        'custom_price',
        'user_id',
        'branch_id',
        'date',
    ];

    protected $casts = [
        'created_at' => 'date',
        'updated_at' => 'date',
        'date' => 'date',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class, 'principal_id');
    }

    public function source()
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
