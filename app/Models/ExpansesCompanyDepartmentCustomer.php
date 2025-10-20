<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpansesCompanyDepartmentCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'expanses_company_detail_id',
        'department',
        'customer_name',
    ];

    public function companyDetail()
{
    return $this->belongsTo(ExpansesCompanyDetail::class, 'expanses_company_detail_id');
}
}
