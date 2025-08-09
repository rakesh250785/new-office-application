<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingQuotation extends Model
{

    protected $table = 'pending_quotations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'quotation_id',
        'unique_quotation_no',
        'reason',
        'total_amount',
        'staus_code',
        'last_updated_at',
        'follow_up_date',
        'branch_id',
        'user_id',
        'reason_status_id',
    ];
}
