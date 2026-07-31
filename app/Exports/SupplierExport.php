<?php

namespace App\Exports;

use App\Helpers\Utility;
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

class SupplierExport implements FromCollection, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $filters;

    protected array $columns;

    protected string $modelClass;

    protected int $userId;

    public function __construct(array $filters, array $columns, string $modelClass, int $userId)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->modelClass = $modelClass;
        $this->userId  = $userId;
    }

    public function collection(): Collection
    {
        $query = ($this->modelClass)::query()
            ->with([
                'product:id,part_no,description',
                'principal:id,type',
                'source:id,name',
                'currency:id,name',
                'branch:id,name',
            ])
            ->whereHas('product', function ($q) {
                $q->whereNotNull('part_no')
                    ->where('part_no', '!=', '');
            })
            ->whereNull('suppliers.deleted_at');
        // Apply same filters as controller
        if (! empty($this->filters['owner'])) {
            $query->where('suppliers.user_id', $this->filters['owner']);
        }
        if (! empty($this->filters['branch'])) {
            $query->where('suppliers.branch_id', $this->filters['branch']);
        }
        if (! empty($this->filters['principal'])) {
            $query->where('suppliers.principal_id', $this->filters['principal']);
        }
        if (! empty($this->filters['product'])) {
            $query->where('suppliers.product_id', $this->filters['product']);
        }
        if (! empty($this->filters['source'])) {
            $query->where('suppliers.source_id', $this->filters['source']);
        }
        if (! empty($this->filters['currency'])) {
            $query->where('suppliers.currency_id', $this->filters['currency']);
        }
        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $query->whereBetween('suppliers.date', [$this->filters['start_date'], $this->filters['end_date']]);
        }
        if (Utility::checkViewPermission('supplier', $this->userId)) {
            $query->where('user_id', $this->userId );
        }
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($q2) use ($search) {
                    $q2->where('part_no', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%");
                })
                    ->orWhere('suppliers.rate_fc', 'like', "%$search%")
                    ->orWhere('suppliers.factor_fc', 'like', "%$search%")
                    ->orWhere('suppliers.total_cost', 'like', "%$search%")
                    ->orWhere('suppliers.discount', 'like', "%$search%")
                    ->orWhere('suppliers.net_price', 'like', "%$search%")
                    ->orWhere('suppliers.custom_price', 'like', "%$search%")
                    ->orWhereHas('principal', fn ($q2) => $q2->where('type', 'like', "%$search%"))
                    ->orWhereHas('source', fn ($q2) => $q2->where('name', 'like', "%$search%"))
                    ->orWhereHas('currency', fn ($q2) => $q2->where('name', 'like', "%$search%"))
                    ->orWhereHas('branch', fn ($q2) => $q2->where('name', 'like', "%$search%"));
            });
        }

        $all = collect();
        $query->orderBy('id')->chunk(5000, function ($rows) use (&$all) {
            foreach ($rows as $row) {
                $all->push($row);
            }
        });

        return $all;
    }

    public function map($row): array
    {
        return collect(array_keys($this->columns))
            ->map(function ($key) use ($row) {
                $value = data_get($row, $key, '');
                if ($key == 'product.part_no') {
                    return ' '.(string) $value;
                }

                return $value;
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
