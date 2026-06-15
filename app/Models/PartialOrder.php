<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PartialOrder extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'unique_partial_order_no',
        'unique_order_no',
        'unique_quotation_no',
        'quotation_id',
        'order_id',
        'company_id',
        'billing_address',
        'billing_city',
        'billing_mobile',
        'billing_email',
        'billing_landline',
        'billing_pin_code',
        'billing_state_id',
        'billing_contact_person',
        'shipping_address',
        'shipping_city',
        'shipping_state_id',
        'shipping_pin_code',
        'shipping_mobile',
        'shipping_email',
        'shipping_landline',
        'product_description',
        'delivery_date_id',
        'lead_from',
        'notification_id',
        'owner_id',
        'quotation_type_id',
        'payment_term_condition',
        'date',
        'enq_ref',
        'prepard_by',
        'branch_id',
        'pdf_name',
        'currency_id',
        'courier_id',
        'tin_number',
        'user_id',
        'total_amount',
        'customer_order_no',
        'extra_notes',
        'partial_order_status',
    ];

    public function partialOrders()
    {
        return $this->hasMany(PartialOrder::class, 'partial_order_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'cusomer_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'unique_quotation_id', 'id');
    }

    public function branchAddress()
    {
        return $this->belongsTo(QuotationFormat::class, 'quotation_format_id', 'id')
            ->whereNull('deleted_at');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function companyDetails()
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(PartialOrderDetails::class, 'partial_order_id', 'id');
    }

    public function ownerDetails()
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function branchDetails()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function currencyDetails()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function principalDetails()
    {
        return $this->belongsTo(Principal::class, 'principal_id');
    }

    public function pendingQuotationDetails()
    {
        return $this->hasOne(PendingQuotation::class, 'quotation_id', 'id');
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }
}
