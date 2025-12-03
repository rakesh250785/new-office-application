<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestQuotationModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name','person_name','email','mobile','address',
        'subtotal','discount_total','gst_amount','total_amount','gst_percent','created_by','status'
    ];

    public function items()
    {
        return $this->hasMany(RequestQuotationModel::class);
    }
}
