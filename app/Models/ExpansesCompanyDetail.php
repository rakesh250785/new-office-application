<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpansesCompanyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'concern_person_name',
        'contact_details',
        'phone_no',
        'email_id',
        'user_id',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    protected $casts = [
        'created_at' => 'date',
        'updated_at' => 'date',
    ];

    public function departmentCustomers()
    {
        return $this->hasMany(ExpansesCompanyDepartmentCustomer::class, 'expanses_company_detail_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Customer::class, 'company_id', 'id')->select('id', 'company_name', 'customer_name');
    }

    public function travelExpanses()
    {
        return $this->hasOne(TravelExpanses::class, 'expanses_company_detail_id', 'id');
    }

    public function linkOrder()
    {
        return $this->hasOne(LinkExpansesOrder::class, 'expanses_company_detail_id', 'id');
    }

    public function paymentBill()
    {
        return $this->hasOne(BillExpansesPayment::class, 'expanses_company_detail_id', 'id');
    }

    public function serviceReport()
    {
        return $this->hasOne(ExpansesServiceReport::class, 'expanses_company_detail_id', 'id')->with('company');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->select('username', 'team_type', 'id', 'branch_id')->with('branch');

    }
}
