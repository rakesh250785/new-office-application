<?php

namespace App\Exports;

use App\Helpers\Utility;
use App\Models\QuotationDetail;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotationExport implements FromQuery, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $filters;

    protected array $columns;

    protected int $userId;

    protected int $branchId;

    public function __construct(array $filters, array $columns, int $userId, int $branchId)
    {
        $this->filters = $filters;
        $this->columns = $columns;
        $this->userId = $userId;
        $this->branchId = $branchId;
    }

    /**
     * STREAMED QUERY — ZERO MEMORY SPIKES
     */
    public function query()
    {
        $q = QuotationDetail::query()
            ->with([
                'quotation:id,unique_quotation_no,date,created_at,branch_id,owner_id,currency_id,company_id,is_order_pending,total_amount,product_description,lead_from,billing_contact_person',
                'quotation.branchDetails:id,name',
                'quotation.ownerDetails:id,name',
                'quotation.currencyDetails:id,code',
                'quotation.companyDetails:id,company_name,customer_name,mobile_no,landline_no,email_id',
                'quotation.pendingQuotationDetails',
                'principal:id,type',
            ])
            ->whereHas('quotation', function ($query) {
                $query->whereNull('deleted_at');
            });

        /* ---------------- FILTERS ---------------- */

        if (! empty($this->filters['branch_list'])) {
            $q->whereHas('quotation', function ($query) {
                $query->where('branch_id', $this->filters['branch_list']);
            });
        }

        if (! empty($this->filters['owner_list'])) {
            $q->whereHas('quotation', function ($query) {
                $query->where('owner_id', $this->filters['owner_list']);
            });
        }

        if (! empty($this->filters['currency_list'])) {
            $q->whereHas('quotation', function ($query) {
                $query->where('currency_id', $this->filters['currency_list']);
            });
        }

        if (! empty($this->filters['status_list'])) {
            $q->whereHas('quotation', function ($query) {
                $query->where('is_order_pending', $this->filters['status_list']);
            });
        }

        if (! empty($this->filters['principal_list'])) {
            $q->where('principal_id', $this->filters['principal_list']);
        }

        /* ---------------- DATE FILTER ---------------- */

        if (
            ! empty($this->filters['start_date']) &&
            ! empty($this->filters['end_date'])
        ) {
            $q->whereHas('quotation', function ($query) {
                $query->whereBetween('created_at', [
                    Carbon::parse($this->filters['start_date'])->startOfDay(),
                    Carbon::parse($this->filters['end_date'])->endOfDay(),
                ]);
            });
        }

        /* ---------------- PERMISSIONS ---------------- */

        if (
            Utility::checkViewPermission('quotation_report') ||
            Utility::checkBranchesViewPermission('quotation_report')
        ) {

            $q->where(function ($query) {

                if (Utility::checkViewPermission('quotation_report')) {
                    $query->orWhere('user_id', $this->userId);
                }

                if (Utility::checkBranchesViewPermission('quotation_report')) {
                    $query->orWhere('branch_id', $this->branchId);
                }
            });
        }

        /* ---------------- SEARCH ---------------- */

        $term = trim($this->filters['search'] ?? '');

        if ($term !== '') {

            $q->where(function ($query) use ($term) {

                // Quotation Detail fields
                $query->where('part_no', 'LIKE', "%{$term}%");

                // Principal
                $query->orWhereHas('principal', function ($principal) use ($term) {
                    $principal->where('type', 'LIKE', "%{$term}%");
                });

                // Quotation + Related Tables
                $query->orWhereHas('quotation', function ($quotation) use ($term) {

                    $quotation->where(function ($q) use ($term) {
                        $q->where('unique_quotation_no', 'LIKE', "%{$term}%")
                            ->orWhere('lead_from', 'LIKE', "%{$term}%")
                            ->orWhere('total_amount', 'LIKE', "%{$term}%");
                    });

                    $quotation->orWhereHas('ownerDetails', function ($owner) use ($term) {
                        $owner->where('name', 'LIKE', "%{$term}%");
                    });

                    $quotation->orWhereHas('currencyDetails', function ($currency) use ($term) {
                        $currency->where('code', 'LIKE', "%{$term}%");
                    });

                    $quotation->orWhereHas('companyDetails', function ($company) use ($term) {
                        $company->where(function ($q) use ($term) {
                            $q->where('company_name', 'LIKE', "%{$term}%")
                                ->orWhere('customer_name', 'LIKE', "%{$term}%");
                        });
                    });
                });
            });
        }

        return $q->orderBy('id');
    }

    /**
     * MAP = PURE ARRAY, NO LOGIC
     */
    public function map($d): array
    {
        $q = $d->quotation;

        return [
            // Branch
            $q->branchDetails->name ?? '',

            // Date
            $q->date
                ? Carbon::parse($q->date)->format('d-m-Y')
                : optional($q->created_at)->format('d-m-Y'),

            // Quotation
            $q->unique_quotation_no ?? '',

            // Company
            $q->companyDetails->company_name ?? '',
            $q->billing_contact_person ?? '',

            // Owner & contacts
            $q->ownerDetails->name ?? '',
            $q->companyDetails->mobile_no ?? '',
            $q->companyDetails->landline_no ?? '',
            $q->companyDetails->email_id ?? '',

            // Line item
            $d->part_no ?? '',
            $d->description ?? '',
            $d->principal ?? '',
            $d->price ?? 0,
            $d->quantity ?? 0,
            $d->discount ?? 0,
            $d->net_price ?? 0,

            // Totals & meta
            $q->total_amount ?? 0,
            $d->notes ?? '',

            $q->lead_from ?? '',

            // Status
            $q->is_order_pending ? 'Pending' : 'Closed',

            // Reason
            $q->pendingQuotationDetails->status_code ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Branch',
            'Date',
            'Quotation No',
            'Company Name',
            'Customer Name',
            'Owner Name',
            'Contact Number',
            'Landline',
            'Contact Email',
            'Part No.',
            'Description',
            'Principal',
            'Price',
            'Quantity',
            'Discount',
            'Net Price',
            'Total Amount',
            'Delivery Status',
            'Lead From',
            'Status',
            'Reason',
        ];
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
