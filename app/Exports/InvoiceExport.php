<?php

namespace App\Exports;

use App\Helpers\Utility;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceExport implements FromQuery, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $filters;

    protected array $columns;

    protected int $userId;

    protected int $branchId;

    /**
     * $filters: same shape as controller (branch_list, owner_list, currency_list, principal_list, start_date, end_date, search)
     * $columns: associative array key => heading
     */
    public function __construct(array $filters, array $columns, int $userId, int $branchId)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->userId = $userId;
        $this->branchId = $branchId;
    }

    public function query()
    {
        $select = [
            'id',
            'invoice_no',
            'partial_order_id',
            'branch_id',
            'created_at',
        ];

        $q = Invoice::query()
            ->whereNull('deleted_at')
            ->select($select)
            ->with([
                'partialOrder:id,unique_partial_order_no,customer_order_no,company_id,courier_id',
                'partialOrder.companyDetails:id,company_name',
                'partialOrder.courier:id,name',
            ]);

        if (! empty($this->filters['branch_list'])) {
            $q->where('branch_id', $this->filters['branch_list']);
        }

        if (! empty($this->filters['owner_list'])) {
            $q->whereHas('customerDetails.owner', fn ($qq) => $qq->where('id', $this->filters['owner_list']));
        }

        if (! empty($this->filters['currency_list'])) {
            $q->whereHas('partialOrder.orderDetails', fn ($qq) => $qq->where('currency_id', $this->filters['currency_list']));
        }

        if (! empty($this->filters['principal_list'])) {
            $q->whereHas('partialOrder.orderDetails', fn ($qq) => $qq->where('principal_id', $this->filters['principal_list']));
        }

        if (
            Utility::checkViewPermission('invoice', $this->userId) ||
            Utility::checkBranchesViewPermission('invoice', $this->userId)
        ) {
            $q->where(function ($q) {

                if (Utility::checkViewPermission('invoice', $this->userId)) {
                    $q->orWhere('user_id', $this->userId);
                }

                if (Utility::checkBranchesViewPermission('invoice', $this->userId)) {
                    $q->orWhere('branch_id', $this->branchId);
                }
            });
        }

        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $q->whereBetween('created_at', [
                Carbon::parse($this->filters['start_date'])->startOfDay(),
                Carbon::parse($this->filters['end_date'])->endOfDay(),
            ]);
        }

        if (! empty($this->filters['search'])) {
            $term = $this->filters['search'];

            $q->where(function ($sub) use ($term) {
                $sub->where('invoice_no', 'like', "%{$term}%")
                    ->orWhere('docket_no', 'like', "%{$term}%")
                    ->orWhereHas('customerDetails', function ($c) use ($term) {
                        $c->where('company_name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('partialOrder', function ($p) use ($term) {
                        $p->where('customer_order_no', 'like', "%{$term}%");
                    })
                    ->orWhereHas('partialOrder.courier', function ($courier) use ($term) {
                        $courier->where('name', 'like', "%{$term}%");
                    });
            });
        }

        return $q->orderByDesc('id');
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function map($invoice): array
    {
        $mapped = [];

        foreach (array_keys($this->columns) as $key) {
            switch ($key) {
                case 'invoice_no':
                    $mapped[] = $invoice->invoice_no;
                    break;
                case 'invoice_date':
                    $mapped[] = $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->format('Y-m-d') : optional($invoice->created_at)->format('Y-m-d');
                    break;
                case 'partial_order_no':
                    $mapped[] = $invoice->partialOrder->unique_partial_order_no ?? $invoice->partial_order_id ?? '';
                    break;
                case 'customer_order_no':
                    $mapped[] = $invoice->partialOrder->customer_order_no ?? '';
                    break;
                case 'customer':
                    $mapped[] = $invoice->customerDetails->company_name ?? $invoice->partialOrder->companyDetails->company_name ?? '';
                    break;
                case 'courier':
                    $mapped[] = $invoice->partialOrder->courier->name ?? $invoice->partialOrder->courier->name ?? '';
                    break;
                case 'branch':
                    $mapped[] = data_get($invoice, 'branchDetails.name', ''); // branchDetails may not be loaded here
                    break;
                default:
                    $mapped[] = data_get($invoice, $key, '');
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
