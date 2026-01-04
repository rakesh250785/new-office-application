<?php

namespace App\Exports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UspExport implements FromCollection, WithMapping, WithHeadings, WithChunkReading, ShouldQueue
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
            ->with(['branch:id,name', 'principal:id,type', 'category:id,name'])
            ->whereNull('deleted_at');

        # Apply search
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('usp_type', 'like', "%$search%")
                    ->orWhere('packing_details', 'like', "%$search%")
                    ->orWhere('usp_brand', 'like', "%$search%")
                    ->orWhereHas('branch', fn($b) => $b->where('name', 'like', "%$search%"))
                    ->orWhereHas('principal', fn($b) => $b->where('type', 'like', "%$search%"))
                    ->orWhereHas('categoryType', fn($b) => $b->where('name', 'like', "%$search%"));
            });
        }

        # Branch filter
        if (!empty($this->filters['branch_list'])) {
            $query->whereIn('branch_id', (array) $this->filters['branch_list']);
        }

        # Principal filter
        if (!empty($this->filters['principal_list'])) {
            $query->whereIn('principal_id', (array) $this->filters['principal_list']);
        }

        # Category filter
        if (!empty($this->filters['category_list'])) {
            $query->whereIn('category_id', (array) $this->filters['category_list']);
        }

        # Date range filter
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['start_date'] . ' 00:00:00',
                $this->filters['end_date'] . ' 23:59:59',
            ]);
        }

        # Collect data in chunks
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
     * Headings for Excel
     */
    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * Chunk size
     */
    public function chunkSize(): int
    {
        return 5000;
    }
}
