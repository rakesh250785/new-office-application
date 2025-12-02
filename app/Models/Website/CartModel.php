<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartModel extends Model
{
    use SoftDeletes;

    protected $table = 'carts';

    protected $fillable = [
        'user_id',
        'cart_token',
        'currency',
        'items',
        'items_count',
        'distinct_items',
        'sub_total',
        'discount_total',
        'tax_total',
        'shipping_total',
        'grand_total',
        'status',
        'metadata',
        'expires_at',
    ];
}
