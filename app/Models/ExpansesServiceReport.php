<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpansesServiceReport extends Model
{
    protected $fillable = [
        'expanses_company_detail_id',
        'company_id',
        'order_no',
        'uploaded_file',
        'totals',
        'user_id',
    ];

    protected $casts = [
        'uploaded_file' => 'array',
        'totals' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Customer::class, 'company_id', 'id')->select('id', 'company_name', 'customer_name');
    }
    
}
