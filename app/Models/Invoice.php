<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $primaryKey = 'id';
    protected $fillable = [
        'customer_order_no',
        'docket_no',
        'invoice_docs',
        'invoice_no',
        'partial_order_id',
        'customer_id',
        'branch_id',
        'user_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function partialOrder()
    {
        return $this->belongsTo(PartialOrder::class, 'partial_order_id');

    }

    public function customerDetails()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
