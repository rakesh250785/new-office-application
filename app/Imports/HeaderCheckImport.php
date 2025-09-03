<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HeaderCheckImport implements ToCollection, WithHeadingRow
{
    public $headers = [];

    public function collection(Collection $rows)
    {
        if ($rows->isNotEmpty()) {
            $this->headers = array_keys($rows->first()->toArray());
        }
    }
}
