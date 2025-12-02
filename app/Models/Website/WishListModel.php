<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WishlistModel extends Model
{
    use HasFactory;

    protected $table = 'wishlists';

    protected $guarded = ['id'];

    protected $casts = [
        'items' => 'array',
        'items_count' => 'integer',
        'distinct_items' => 'integer',
        'sub_total' => 'decimal:4',
    ];

    public static function emptySkeleton($userId = null)
    {
        return [
            'id' => null,
            'user_id' => $userId,
            'items' => [],
            'items_count' => 0,
            'distinct_items' => 0,
            'sub_total' => 0,
        ];
    }
}
