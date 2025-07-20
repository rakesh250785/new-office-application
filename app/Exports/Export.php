<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Export implements FromQuery, WithHeadings
{
    protected $query;
    protected $columns;

    public function __construct(Builder $query, array $columns)
    {
        $this->query = $query->select($columns);
        $this->columns = $columns;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return $this->columns;
    }
}
