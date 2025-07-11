<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customer';
    protected $primaryKey = 'id';
    public function partialOrders()
    {
        return $this->hasMany(PartialOrder::class, 'customer_id', 'id');
    }
}

