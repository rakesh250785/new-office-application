<?php

namespace App\Exports;

use App\Models\Courier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CourierExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading, ShouldQueue
{
    use Exportable;

    protected array $filters;
    protected array $columns;

    public function __construct(array $filters, array $columns)
    {
        $this->filters = $filters;
        $this->columns = $columns;
    }

    public function query()
    {
        $query = Courier::query()->whereNull('deleted_at');
        if (!empty($this->filters['branch_list'])) {
            $query->where('branch_id', $this->filters['branch_list']);
        }

        if (!empty($this->filters['courier_name'])) {
            $query->where('name', 'like', '%' . $this->filters['courier_name'] . '%');
        }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['start_date'] . ' 00:00:00',
                $this->filters['end_date'] . ' 23:59:59',
            ]);
        }

        return $query->select(array_keys($this->columns));
    }

   public function map($row): array
    {
        return collect(array_keys($this->columns))
            ->map(fn($key) => data_get($row, $key, ''))
            ->toArray();
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function chunkSize(): int
    {
        return 5000;
    }
}
