<?php

namespace App\Models\Website;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ColumnApprovalModel extends Model
{
    protected $table = 'column_approval';

    protected $fillable = [
        'sample',
        'pharmacopoeia',
        'sales_person',
        'request_date',
        'application_type',
        'matrices',

        'column_sample_analysis',
        'column_column',
        'column_hplc',
        'column_gc',

        'organisation',
        'location',
        'department',
        'contact_name',
        'designation',
        'email_or_fax',
        'mobile',

        'in_use_column_description',
        'required_column_description',
        'problem_description',
        'is_guard_column_used',
        'guard_column_details',
        'part_no',
        'is_brand_change_acceptable',

        'diluents_solvent',
        'standard_preparation',
        'mobile_phase',
        'flow_rate_per_min',
        'gradient_temp_program',
        'injection_volume',
        'detector',
        'detector_settings',
        'instrument_used',
        'additional_information',
        'expected_column_consumption',

        'analytical_method_monograph',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
