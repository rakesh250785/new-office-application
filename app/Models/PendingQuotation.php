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
        'amount',
        'reason_mode',
        'last_updated_at'
    ];
}
