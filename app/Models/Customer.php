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
        'contact_person',
        'email_id',
        'mobile_no',
        'landline_no',
        'address',
        'customer_id',
        'owner_id',
        'country_id',
        'unique_quotation_no',
        'state_id',
        'classification_id',
        'other_state',
        'pin_code',
        'city',
        'user_id',
        'branch_id',
        'shipping_address',
        'shiping_city',
        'shipping_state_id',
        'shiping_pin_code',
        'shipping_phone',
        'shipping_email',
        'shipping_landline',
        'pdf_name'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y');
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

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderDetails()
    {
        return $this->hasManyThrough(
            OrderDetails::class,
            Order::class,
            'company_id',
            'order_id',
            'id',
            'id'
        );
    }

}

