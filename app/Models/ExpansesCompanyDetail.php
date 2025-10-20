<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpansesCompanyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'concern_person_name',
        'designation',
        'contact_details',
        'phone_no',
        'email_id',
    ];

    public function departmentCustomers()
    {
        return $this->hasMany(ExpansesCompanyDepartmentCustomer::class, 'expanses_company_detail_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Customer::class, 'company_id', 'id')->select('id','company_name', 'customer_name');
    }
}
