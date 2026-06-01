<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Usp extends Model
{
    protected $fillable = [
        'usp_type',
        'packing_details',
        'usp_brand',
        'category_id',
        'principal_id',
        'branch_id',
        'user_id',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
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

    // public function categoryType()
    // {
    //     return $this->belongsTo(CategoryType::class, 'category_id');
    // }

    
}
