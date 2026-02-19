<?php

namespace App\Exports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ParameterExport implements FromCollection, WithMapping, WithHeadings, WithChunkReading, ShouldQueue
{
    use Exportable;

    protected $filters;
    protected $columns;
    protected $modelClass;

    public function __construct(array $filters, array $columns, string $modelClass)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->modelClass = $modelClass;
    }

    /**
     * Collect data in chunks for export
     */
    public function collection(): Collection
    {
        $query = ($this->modelClass)::query()
            ->with('branch:id,name')
            ->whereNull('deleted_at');

        # Search filter
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('parameter_name', 'like', "%$search%")
                    ->orWhere('column_name', 'like', "%$search%")
                    ->orWhereHas('branch', fn($b) => $b->where('name', 'like', "%$search%"));
            });
        }

        # Branch filter
        if (!empty($this->filters['branch_list'])) {
            $query->where('branch_id', $this->filters['branch_list']);
        }

        # Date range filter
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['start_date'] . ' 00:00:00',
                $this->filters['end_date'] . ' 23:59:59',
            ]);
        }

        $all = collect();
        $query->orderBy('id')->chunk(5000, function ($rows) use (&$all) {
            foreach ($rows as $row) {
                $all->push($row);
            }
        });

        return $all;
    }

    /**
     * Map each row for Excel
     */
    public function map($row): array
    {
        return collect($this->columns)
            ->keys()
            ->map(fn($key) => data_get($row, $key, ''))
            ->toArray();
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * Chunk size for reading
     */
    public function chunkSize(): int
    {
        return 5000;
    }
}
