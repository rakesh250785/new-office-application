<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $primaryKey = 'id';
    protected $fillable = [
        'customer_order_no',
        'invoice_docs',
        'invoice_no',
        'partial_order_id',
        'customer_id',
        'branch_id',
        'user_id'
    ];

    public function partialOrder()
    {
        return $this->belongsTo(PartialOrder::class, 'id', 'partial_order_id');

    }

    public function customerDetails()
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }

    public function courierDetails()
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }
}
