<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    protected $fillable = [
        'file_name',
        'upload_type',
        'status',
        'total_rows',
        'processed_rows',
    ];
}
