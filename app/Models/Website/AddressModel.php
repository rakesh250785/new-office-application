<?php

namespace App\Models\Website;

use App\Models\City;
use App\Models\Country;
use App\Models\States;
use Illuminate\Database\Eloquent\Model;

class AddressModel extends Model
{
    protected $table = 'address';

    protected $fillable = [
        'user_id',
        'web_user_id',
        'first_name',
        'last_name',
        'company',
        'mobile',
        'address1',
        'address2',
        'pincode',
        'country_id',
        'state_id',
        'city_id',
        'is_billing_address',
        'is_shipping_address',
    ];

    protected $casts = [
        'is_billing_address' => 'boolean',
        'is_shipping_address' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(WebUser::class, 'user_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(States::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
