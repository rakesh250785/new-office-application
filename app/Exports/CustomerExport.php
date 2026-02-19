<?php

namespace App\Exports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerExport implements FromCollection, ShouldQueue, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    protected array $columns;

    protected string $modelClass;

    protected int $chunk = 5000;

    public function __construct(array $filters, array $columns, string $modelClass)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->modelClass = $modelClass;
    }

    /**
     * Build and return a collection for export.
     */
    public function collection(): Collection
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::query()
            ->with($this->collectRelations())
            ->whereNull($modelClass::getModel()->getTable().'.deleted_at');

        // date filter (created_at)
        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $start = $this->filters['start_date'].' 00:00:00';
            $end = $this->filters['end_date'].' 23:59:59';
            $query->whereBetween('created_at', [$start, $end]);
        } elseif (! empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        } elseif (! empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        // branch filter
        if (! empty($this->filters['branch_list'])) {
            $query->where('branch_id', $this->filters['branch_list']);
        }

        // owner filter
        if (! empty($this->filters['owner_list'])) {
            $query->where('owner_id', $this->filters['owner_list']);
        }

        // search across fields & relations
        if (! empty($this->filters['search'])) {
            $search = trim($this->filters['search']);
            $like = '%'.$search.'%';

            $query->where(function ($q) use ($like) {
                $q->where('customer_name', 'like', $like)
                    ->orWhere('company_name', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('pin_code', 'like', $like)
                    ->orWhere('gst_number', 'like', $like)
                    ->orWhere('mobile_no', 'like', $like)
                    ->orWhere('landline_no', 'like', $like)
                    ->orWhere('other_state', 'like', $like)
                    ->orWhereHas('classification', fn ($b) => $b->where('name', 'like', $like))
                    ->orWhereHas('country', fn ($b) => $b->where('name', 'like', $like))
                    ->orWhereHas('state', fn ($b) => $b->where('name', 'like', $like))
                    ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', $like))
                    ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', $like));
            });
        }

        $all = collect();

        $query->orderBy('id')->chunk($this->chunk, function ($rows) use (&$all) {
            foreach ($rows as $row) {
                $all->push($row);
            }
        });

        return $all;
    }

    /**
     * Map a row (model) to an array for the export.
     *
     * Supports dot notation in $this->columns keys (e.g. 'owner.name').
     *
     * @param  mixed  $row
     */
    public function map($row): array
    {
        return collect(array_keys($this->columns))
            ->map(fn ($key) => $this->getValue($row, $key))
            ->toArray();
    }

    /**
     * Headings for the exported file — values of $this->columns.
     */
    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * Chunk size used by WithChunkReading / chunk() logic.
     */
    public function chunkSize(): int
    {
        return $this->chunk;
    }

    /**
     * Extract relation names from column keys (owner.name => owner).
     */
    protected function collectRelations(): array
    {
        $relations = [];
        foreach (array_keys($this->columns) as $key) {
            if (Str::contains($key, '.')) {
                $relations[] = explode('.', $key)[0];
            }
        }

        return array_values(array_unique($relations));
    }

    /**
     * Safely get nested value using dot notation (works with relations & attributes).
     *
     * @param  mixed  $row
     * @return mixed
     */
    protected function getValue($row, string $key)
    {
        return data_get($row, $key, '');
    }
}
