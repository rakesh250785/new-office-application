<?php

namespace App\Exports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CustomerSummaryExport extends DefaultValueBinder implements FromCollection, ShouldQueue, WithChunkReading, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping
{
    use Exportable;

    protected array $headers;

    protected Collection $rows;

    public function __construct(array $headers, $rows, $totals = null)
    {
        $this->headers = $headers;

        $collection = collect($rows)->map(function ($row) {
            return is_object($row) ? (array) $row : $row;
        });

        if ($totals) {
            $collection->push(
                is_object($totals) ? (array) $totals : $totals
            );
        }

        $this->rows = $collection;
    }

    /*
    |--------------------------------------------------------------------------
    | Data Source
    |--------------------------------------------------------------------------
    */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Force Proper Excel Cell Types
    |--------------------------------------------------------------------------
    */
    public function bindValue(Cell $cell, $value)
    {
        // Column A = customer name
        if ($cell->getColumn() === 'A') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        // Null or empty → numeric zero
        if ($value === null || $value === '') {
            $cell->setValueExplicit(0, DataType::TYPE_NUMERIC);

            return true;
        }

        if (is_numeric($value)) {
            $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Map Rows According to Headers
    |--------------------------------------------------------------------------
    */
    public function map($row): array
    {
        $row = is_object($row) ? (array) $row : $row;

        $mapped = [];

        foreach ($this->headers as $header) {
            $mapped[] = $row[$header] ?? 0;
        }

        return $mapped;
    }

    /*
    |--------------------------------------------------------------------------
    | Headings
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        return $this->headers;
    }

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    */
    public function chunkSize(): int
    {
        return 5000;
    }

    /*
    |--------------------------------------------------------------------------
    | Number Formatting (0.00)
    |--------------------------------------------------------------------------
    */
    public function columnFormats(): array
    {
        $formats = [];

        // Skip column A (customer name)
        for ($i = 2; $i <= count($this->headers); $i++) {
            $column = Coordinate::stringFromColumnIndex($i);
            $formats[$column] = NumberFormat::FORMAT_NUMBER_00;
        }

        return $formats;
    }
}
