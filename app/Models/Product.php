<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'part_no',
        'hsn_no',
        'price',
        'uom',
        'igst_rate',
        'discount',
        'description',
        'additional_description',
        'specification',
        'category_id',
        'brand_id',
        'principal_id',
        'quantity',
        'price_updated_at',
        'quantity_updated_at',
        'branch_id',
        'user_id',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getPriceUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getQuantityUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class, 'principal_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function details()
    {
        return $this->belongsTo(ProductDetail::class, 'brand_id');
    }

    public function parameterField()
    {

    }
}
