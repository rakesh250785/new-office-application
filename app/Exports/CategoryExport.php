<?php

namespace App\Exports;

use App\Helpers\Utility;
use App\Models\Parameter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryExport implements FromCollection, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $filters;

    protected array $columns;

    protected string $modelClass;

    protected array $paramMap = [];

    public function __construct(array $filters, array $columns, string $modelClass)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->modelClass = $modelClass;
    }

    public function collection(): Collection
    {
        $query = ($this->modelClass)::query()
            ->with('branch:id,name')
            ->whereNull('deleted_at');

        //  Search filter (including parameter names)
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $paramIds = Parameter::where('parameter_name', 'like', "%$search%")
                ->pluck('id')
                ->toArray();

            $query->where(function ($q) use ($search, $paramIds) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%$search%"));

                if (! empty($paramIds)) {
                    foreach ($paramIds as $id) {
                        $q->orWhereRaw('FIND_IN_SET(?, parameter_field)', [$id]);
                    }
                }
            });
        }

        if (Utility::checkViewPermission('category')) {
            $query->where('user_id', Auth::id());
        }

        //  Branch filter
        if (! empty($this->filters['branch_list'])) {
            $query->where('branch_id', $this->filters['branch_list']);
        }

        //  Date range filter
        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['start_date'].' 00:00:00',
                $this->filters['end_date'].' 23:59:59',
            ]);
        }

        //  Fetch parameter map once (for all IDs)
        $allParamIds = $query->pluck('parameter_field')
            ->flatMap(fn ($field) => explode(',', $field ?? ''))
            ->filter()
            ->unique()
            ->toArray();

        $this->paramMap = Parameter::whereIn('id', $allParamIds)
            ->pluck('parameter_name', 'id')
            ->toArray();

        //  Collect data in chunks
        $all = collect();
        $query->orderBy('id')->chunk(5000, function ($rows) use (&$all) {
            foreach ($rows as $row) {
                // Attach parameter_fields dynamically
                $ids = array_filter(array_map('intval', explode(',', $row->parameter_field ?? '')));
                $row->parameter_fields = collect($ids)
                    ->map(fn ($id) => $this->paramMap[$id] ?? null)
                    ->filter()
                    ->implode(', ');

                $all->push($row);
            }
        });

        return $all;
    }

    public function map($row): array
    {
        return collect($this->columns)
            ->keys()
            ->map(function ($key) use ($row) {
                return data_get($row, $key, '');
            })
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
