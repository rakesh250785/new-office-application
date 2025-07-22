<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Export implements FromCollection, WithMapping, WithHeadings
{
    protected $data;
    protected $columns;

    public function __construct($data, $columns)
    {
        $this->data = $data;
        $this->columns = $columns;
    }

    public function collection()
    {
        return collect($this->data);
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
