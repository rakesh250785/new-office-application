<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'quotations';
    protected $primaryKey = 'id';

    protected $fillable = [
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'quotation_id', 'id');
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function pending()
    {
        return $this->hasOne(PendingQuotation::class, 'quotation_id', 'id');
    }
}
