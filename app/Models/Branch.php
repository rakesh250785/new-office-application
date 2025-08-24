<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'branch_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'branch_id');
    }
}
