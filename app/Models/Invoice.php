<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $primaryKey = 'id';
    protected $fillable = [];

    public function partialOrder()
    {
        return $this->belongsTo(PartialOrder::class, 'id', 'partial_order_id');
    }
}
