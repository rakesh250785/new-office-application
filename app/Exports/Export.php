<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Export implements FromQuery, WithMapping, WithHeadings
{
    protected $query;
    protected $columns;

    public function __construct($query, $columns)
    {
        $this->query = $query;
        $this->columns = $columns;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($row): array
    {
        return collect($this->columns)->map(function ($_, $key) use ($row) {
            return data_get($row, $key);
        })->toArray();
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }
}
