<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferModel extends Model
{
    protected $fillable = ['title', 'description', 'start_date', 'end_date', 'status', 'created_by'];

    public function items()
    {
        return $this->hasMany(OfferItemModel::class);
    }
}
