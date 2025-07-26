<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{

    protected $fillable = [
        'gst_number',
        'company_name',
        'customer_name',
        'email_id',
        'mobile_no',
        'landline_no',
        'address',
        'customer_id',
        'owner_id',
        'country_id',
        'state_id',
        'classification_id',
        'other_state',
        'pin_code',
        'city',
        'user_id',
        'branch_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function state()
    {
        return $this->belongsTo(States::class, 'state_id');
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class, 'classification_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function partialOrders()
    {
        return $this->hasMany(PartialOrder::class, 'customer_id', 'id');
    }

}

