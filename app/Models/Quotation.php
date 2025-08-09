<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'quotations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'unique_quotation_no',
        'customer_id',
        'billing_state_id',
        'billing_address',
        'billing_city',
        'billing_mobile',
        'billing_email',
        'billing_landline',
        'billing_pin_code',
        'billing_contact_person',
        'shipping_address',
        'shiping_city',
        'shipping_state',
        'shiping_pin_code',
        'shipping_phone',
        'shipping_email',
        'shipping_state_id',
        'shipping_landline',
        'product_description',
        'delivery_period',
        'lead_from',
        'notification_id',
        'owner_id',
        'quotation_format_type',
        'term_condition_notes',
        'reference_date',
        'date',
        'contact_person',
        'enquiry_reference',
        'quotation_prepare_by',
        'branch_id',
        'pdf_name',
        'currency_id',
        'company_id',
        'delivery_date_id',
        'tin_number',
        'total_amount',
        'user_id'
    ];


    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function companyDetails()
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }

    public function quotationDetails()
    {
        return $this->hasMany(QuotationDetail::class, 'quotation_id', 'id');
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

    public function pending()
    {
        return $this->hasOne(PendingQuotation::class, 'quotation_id', 'id');
    }
}
