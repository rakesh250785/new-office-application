<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'quotations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'unique_quotation_no',
        'customer_id',
        'shipping_address',
        'shiping_city',
        'shipping_state',
        'shiping_pin_code',
        'shipping_phone',
        'shipping_email',
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
        'tin_number',
        'user_id'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'quotation_id', 'id');
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function pending()
    {
        return $this->hasOne(PendingQuotation::class, 'quotation_id', 'id');
    }
}
