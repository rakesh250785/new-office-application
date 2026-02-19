<?php

namespace App\Exports;

use App\Models\QuotationDetail;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuotationExport implements FromQuery, ShouldQueue, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    protected array $columns;

    public function __construct(array $filters, array $columns)
    {
        $this->filters = $filters;
        $this->columns = $columns;
    }

    /**
     * STREAMED QUERY — ZERO MEMORY SPIKES
     */
    public function query()
    {
        $q = QuotationDetail::query()
            ->with([
                'principal:id,type',
                'quotation:id,unique_quotation_no,date,created_at,branch_id,owner_id,currency_id,company_id,is_order_pending,total_amount,product_description,lead_from',
                'quotation.branchDetails:id,name',
                'quotation.ownerDetails:id,name',
                'quotation.currencyDetails:id,code',
                'quotation.companyDetails:id,company_name,customer_name,mobile_no,landline_no,email_id',
                'quotation.pendingQuotationDetails',
            ])
            ->whereHas('quotation', fn ($q) => $q->whereNull('deleted_at'));

        /* ---------------- FILTERS ---------------- */

        if (! empty($this->filters['branch_list'])) {
            $q->whereHas('quotation', fn ($s) => $s->where('branch_id',  $this->filters['branch_list'])
            );
        }

        if (! empty($this->filters['owner_list'])) {
            $q->whereHas('quotation', fn ($s) => $s->where('owner_id',  $this->filters['owner_list'])
            );
        }

        if (! empty($this->filters['currency_list'])) {
            $q->whereHas('quotation', fn ($s) => $s->where('currency_id',  $this->filters['currency_list'])
            );
        }

        if (! empty($this->filters['status_list'])) {
            $q->whereHas('quotation', fn ($s) => $s->where('is_order_pending',  $this->filters['status_list'])
            );
        }

        if (! empty($this->filters['principal_list'])) {
            $q->where('principal_id',  $this->filters['principal_list']);
        }

        if (! empty($this->filters['start_date']) && ! empty($this->filters['end_date'])) {
            $q->whereHas('quotation', fn ($s) => $s->whereBetween('created_at', [
                Carbon::parse($this->filters['start_date'])->startOfDay(),
                Carbon::parse($this->filters['end_date'])->endOfDay(),
            ])
            );
        }

        if (! empty($this->filters['search'])) {
            $term = $this->filters['search'];
            $q->where(function ($s) use ($term) {
                $s->where('part_no', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('principal', fn ($p) => $p->where('type', 'like', "%{$term}%")
                    )
                    ->orWhereHas('quotation', fn ($q) => $q->where('unique_quotation_no', 'like', "%{$term}%")
                    );
            });
        }

        return $q->orderBy('quotation_details.id');
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
            $q->companyDetails->customer_name ?? '',

            // Owner & contacts
            $q->ownerDetails->name ?? '',
            $q->companyDetails->mobile_no ?? '',
            $q->companyDetails->landline_no ?? '',
            $q->companyDetails->email_id ?? '',

            // Line item
            $d->part_no ?? '',
            $d->description ?? '',
            $q->principal->type ?? '',
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

    public function chunkSize(): int
    {
        return 5000;
    }
}
