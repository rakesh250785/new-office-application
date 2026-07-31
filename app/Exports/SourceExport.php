<?php

namespace App\Exports;

use App\Helpers\Utility;
use App\Models\Source;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SourceExport implements FromQuery, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $filters;

    protected array $columns;

    protected string $modelClass;

    protected int $userId;

    public function __construct(array $filters, array $columns, string $modelClass, $userId)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->modelClass = $modelClass;
        $this->userId = $userId;
    }

    public function query(): Builder
    {
        $query = Source::query()
            ->with('branch:id,name')
            ->whereNull('deleted_at');

        // Filters
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%$search%"));
            });
        }

        if (! empty($this->filters['branch_list'])) {
            $query->where('branch_id', $this->filters['branch_list']);
        }

        if (Utility::checkViewPermission('source')) {
            $query->where('user_id', $this->userId);
        }

        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['start_date'].' 00:00:00',
                $this->filters['end_date'].' 23:59:59',
            ]);
        }

        return $query->select(['id', 'name', 'branch_id', 'created_at', 'user_id']);
    }

    public function map($row): array
    {
        return collect(array_keys($this->columns))
            ->map(fn ($key) => data_get($row, $key, ''))
            ->toArray();
    }

    public function headings(): array
    {
        return array_values($this->columns);
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
}
