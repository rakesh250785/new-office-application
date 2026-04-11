<?php

namespace App\Exports;

use App\Helpers\Utility;
use App\Models\Order;
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

class OrderExport implements FromQuery, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles
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
            'is_order_closed',
            'user_id',
        ];

        $q = Order::query()
            ->whereNull('deleted_at')
            ->select($select)
            ->with([
                'branchDetails:id,name',
                'ownerDetails:id,name',
                'currencyDetails:id,code',
                'companyDetails:id,company_name',
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

        if (! empty($this->filters['status_list'])) {
            $q->where('is_order_closed', $this->filters['status_list']);
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
            Utility::checkViewPermission('order_report') ||
            Utility::checkBranchesViewPermission('order_report')
        ) {

            $q->where(function ($q) {

                if (Utility::checkViewPermission('order_report')) {
                    $q->orWhere('user_id', Auth::id());
                }

                if (Utility::checkBranchesViewPermission('order_report')) {
                    $q->orWhere('branch_id', Auth::user()->branch_id);
                }
            });
        }

        if (! empty($this->filters['search'])) {
            $term = $this->filters['search'];
            $q->where(function ($sub) use ($term) {
                $sub->where('unique_order_no', 'like', "%{$term}%")
                    ->orWhere('unique_quotation_no', 'like', "%{$term}%")
                    ->orWhere('customer_order_no', 'like', "%{$term}%")
                    ->orWhere('lead_from', 'like', "%{$term}%")
                    ->orWhereHas('ownerDetails', fn ($o) => $o->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('currencyDetails', fn ($c) => $c->where('code', 'like', "%{$term}%"))
                    ->orWhereHas('companyDetails', fn ($c) => $c->where('company_name', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%"))
                    ->orWhereHas('orderDetails', function ($d) use ($term) {
                        $d->where('part_no', 'like', "%{$term}%")
                            ->orWhereHas('principal', fn ($p) => $p->where('type', 'like', "%{$term}%"));
                    });
            });
        }

        return $q->orderByDesc('id');
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function map($order): array
    {
        $mapped = [];

        foreach (array_keys($this->columns) as $key) {
            switch ($key) {
                case 'unique_order_no':
                    $mapped[] = $order->unique_order_no;
                    break;
                case 'unique_quotation_no':
                    $mapped[] = $order->unique_quotation_no;
                    break;
                case 'date':
                    $mapped[] = $order->date ? Carbon::parse($order->date)->format('Y-m-d') : optional($order->created_at)->format('Y-m-d');
                    break;
                case 'created_at':
                    $mapped[] = optional($order->created_at)->format('Y-m-d H:i:s');
                    break;
                case 'lead_from':
                    $mapped[] = $order->lead_from;
                    break;
                case 'branch':
                case 'branch_name':
                    $mapped[] = $order->branchDetails->name ?? '';
                    break;
                case 'owner':
                case 'owner_name':
                    $mapped[] = $order->ownerDetails->name ?? '';
                    break;
                case 'currency':
                case 'currency_code':
                    $mapped[] = $order->currencyDetails->code ?? '';
                    break;
                case 'company':
                case 'company_name':
                    $mapped[] = $order->companyDetails->company_name ?? '';
                    break;
                case 'total_amount':
                    $mapped[] = $order->total_amount;
                    break;
                case 'customer_order_no':
                    $mapped[] = $order->customer_order_no;
                    break;
                case 'status':
                case 'is_order_closed':
                    $mapped[] = $order->is_order_closed ? 'Closed' : 'Open';
                    break;
                default:
                    $mapped[] = data_get($order, $key, '');
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
