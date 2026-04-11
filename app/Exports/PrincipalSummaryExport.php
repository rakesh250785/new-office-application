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
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrincipalSummaryExport extends DefaultValueBinder implements FromCollection, ShouldQueue, WithChunkReading, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
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

    public function collection(): Collection
    {
        return $this->rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Force numeric cells (fix blank 0.00 issue)
    |--------------------------------------------------------------------------
    */
    public function bindValue(Cell $cell, $value)
    {
        // Column A = principal name
        if ($cell->getColumn() === 'A') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

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

    public function map($row): array
    {
        $row = is_object($row) ? (array) $row : $row;

        $mapped = [];

        foreach ($this->headers as $header) {
            $mapped[] = $row[$header] ?? 0;
        }

        return $mapped;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    public function columnFormats(): array
    {
        $formats = [];

        for ($i = 2; $i <= count($this->headers); $i++) {
            $column = Coordinate::stringFromColumnIndex($i);
            $formats[$column] = NumberFormat::FORMAT_NUMBER_00;
        }

        return $formats;
    }
}
