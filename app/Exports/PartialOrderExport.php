<?php

namespace App\Exports;

use App\Helpers\Utility;
use App\Models\PartialOrder;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartialOrderExport implements FromQuery, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles
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
        $select = [
            'id',
            'unique_partial_order_no',
            'unique_order_no',
            'unique_quotation_no',
            'lead_from',
            'branch_id',
            'owner_id',
            'currency_id',
            'company_id',
            'total_amount',
            'created_at',
            'date',
            'customer_order_no',
            'courier_id',
        ];

        $q = PartialOrder::query()
            ->whereNull('deleted_at')
            ->select($select)
            ->with([
                'branchDetails:id,name',
                'ownerDetails:id,name',
                'currencyDetails:id,code',
                'companyDetails:id,company_name',
                'courier:id,name',
            ]);

        if (! empty($this->filters['branch_list'])) {
            $q->where('branch_id', $this->filters['branch_list']);
        }

        if (! empty($this->filters['owner_list'])) {
            $q->where('owner_id', $this->filters['owner_list']);
        }

        if (! empty($this->filters['currency_list'])) {
            $q->where('currency_id', $this->filters['currency_list']);
        }

        if (! empty($this->filters['principal_list'])) {
            $q->whereHas('orderDetails', fn ($d) => $d->where('principal_id', $this->filters['principal_list']));
        }

        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $q->whereBetween('created_at', [
                Carbon::parse($this->filters['start_date'])->startOfDay(),
                Carbon::parse($this->filters['end_date'])->endOfDay(),
            ]);
        }

        if (
            Utility::checkViewPermission('partial_order') ||
            Utility::checkBranchesViewPermission('partial_order')
        ) {
            $q->where(function ($q) {

                if (Utility::checkViewPermission('partial_order')) {
                    $q->orWhere('user_id', Auth::id());
                }

                if (Utility::checkBranchesViewPermission('partial_order')) {
                    $q->orWhere('branch_id', Auth::user()->branch_id);
                }
            });
        }

        if (! empty($this->filters['search'])) {
            $term = $this->filters['search'];
            $q->where(function ($sub) use ($term) {
                $sub->where('unique_partial_order_no', 'like', "%{$term}%")
                    ->orWhere('unique_order_no', 'like', "%{$term}%")
                    ->orWhere('unique_quotation_no', 'like', "%{$term}%")
                    ->orWhere('customer_order_no', 'like', "%{$term}%")
                    ->orWhereHas('companyDetails', fn ($c) => $c->where('company_name', 'like', "%{$term}%"))
                    ->orWhereHas('orderDetails', function ($d) use ($term) {
                        $d->where('part_no', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhere('hsn_code', 'like', "%{$term}%")
                            ->orWhere('in_stock', 'like', "%{$term}%")
                            ->orWhere('send_qty', 'like', "%{$term}%")
                            ->orWhere('balance_quantity', 'like', "%{$term}%")
                            ->orWhere('quantity', 'like', "%{$term}%")
                            ->orWhere('net_price', 'like', "%{$term}%")
                            ->orWhere('total', 'like', "%{$term}%");
                    });
            });
        }

        return $q->orderByDesc('id');
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function map($po): array
    {
        $mapped = [];
        foreach (array_keys($this->columns) as $key) {
            switch ($key) {
                case 'unique_partial_order_no':
                    $mapped[] = $po->unique_partial_order_no;
                    break;
                case 'unique_order_no':
                    $mapped[] = $po->unique_order_no;
                    break;
                case 'unique_quotation_no':
                    $mapped[] = $po->unique_quotation_no;
                    break;
                case 'date':
                    $mapped[] = $po->date ? Carbon::parse($po->date)->format('Y-m-d') : optional($po->created_at)->format('Y-m-d');
                    break;
                case 'created_at':
                    $mapped[] = optional($po->created_at)->format('Y-m-d H:i:s');
                    break;
                case 'lead_from':
                    $mapped[] = $po->lead_from;
                    break;
                case 'branch':
                    $mapped[] = $po->branchDetails->name ?? '';
                    break;
                case 'owner':
                    $mapped[] = $po->ownerDetails->name ?? '';
                    break;
                case 'currency':
                    $mapped[] = $po->currencyDetails->code ?? '';
                    break;
                case 'company':
                    $mapped[] = $po->companyDetails->company_name ?? '';
                    break;
                case 'total_amount':
                    $mapped[] = $po->total_amount;
                    break;
                case 'customer_order_no':
                    $mapped[] = $po->customer_order_no;
                    break;
                case 'courier':
                    $mapped[] = $po->courier->name ?? $po->courier_id ?? '';
                    break;
                default:
                    $mapped[] = data_get($po, $key, '');
                    break;
            }
        }

        return $mapped;
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
