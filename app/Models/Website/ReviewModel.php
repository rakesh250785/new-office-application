<?php

namespace App\Models\Website;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewModel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'web_user_id', 'rating', 'title', 'body', 'images', 'status',
        'helpful_count', 'reported_count',
    ];

    public function user()
    {
        return $this->belongsTo(WebUser::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }
}
