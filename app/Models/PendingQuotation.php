<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PendingQuotation extends Model
{

    protected $table = 'pending_quotations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'quotation_id',
        'unique_quotation_no',
        'unique_order_no',
        'reason',
        'total_amount',
        'status_code',
        'last_updated_at',
        'follow_up_date',
        'branch_id',
        'user_id',
        'reason_status_id',
    ];

    public function getLastUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
    }
}
