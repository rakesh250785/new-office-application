<?php

namespace App\Exports;

use App\Helpers\Utility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserExport implements FromQuery, ShouldQueue, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    protected $filters;

    protected $columns;

    protected $modelClass;

    public function __construct($filters, $columns, $modelClass)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->modelClass = $modelClass;
    }

    public function query()
    {
        return ($this->modelClass)::query()
            ->with('branch:id,name')
            ->whereNull('deleted_at')

            ->when(! empty($this->filters['branch_list']), function ($q) {
                $q->where('branch_id', (array) $this->filters['branch_list']);
            })

            ->when(Utility::checkViewPermission('user'), function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->when(! empty($this->filters['search']), function ($q) {
                $search = $this->filters['search'];

                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('team_type', 'like', "%{$search}%")
                        ->orWhereHas('branch', function ($b) use ($search) {
                            $b->where('name', 'like', "%{$search}%");
                        });
                });
            })

            ->when(
                ! empty($this->filters['start_date']) && ! empty($this->filters['end_date']),
                function ($q) {
                    $q->whereBetween('created_at', [
                        $this->filters['start_date'].' 00:00:00',
                        $this->filters['end_date'].' 23:59:59',
                    ]);
                }
            );
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

    public function chunkSize(): int
    {
        return 5000;
    }
}
