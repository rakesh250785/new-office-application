<?php

namespace App\Models\Website;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class RatingModel extends Model
{
    protected $table = 'product_ratings';

    // product_id is the PK but not auto-increment
    protected $primaryKey = 'product_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'avg_rating',
        'total_reviews',
        'rating_5',
        'rating_4',
        'rating_3',
        'rating_2',
        'rating_1',
        'updated_at',
    ];

    protected $casts = [
        'avg_rating' => 'float',
        'total_reviews' => 'integer',
        'rating_5' => 'integer',
        'rating_4' => 'integer',
        'rating_3' => 'integer',
        'rating_2' => 'integer',
        'rating_1' => 'integer',
        'updated_at' => 'datetime',
    ];

    public $incrementingKeyType = 'int';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Convenience: return distribution as associative array
     */
    public function distribution(): array
    {
        return [
            5 => (int) $this->rating_5,
            4 => (int) $this->rating_4,
            3 => (int) $this->rating_3,
            2 => (int) $this->rating_2,
            1 => (int) $this->rating_1,
        ];
    }
}
