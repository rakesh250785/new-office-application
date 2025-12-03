<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferItemModel extends Model
{
    protected $fillable = [
        'offer_id', 'product_id', 'offer_price', 'discount_percent', 'qty_limit',
        'igst_percent', 'hsn', 'principal_id', 'category_id', 'image', 'active', 'sort_order',
    ];

    public function offer()
    {
        return $this->belongsTo(OfferModel::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
