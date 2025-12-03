<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnquiryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_id','part_no','description','qty','amount','discount','total'
    ];

    public function enquiry()
    {
        return $this->belongsTo(RequestQuotationModel::class);
    }
}
