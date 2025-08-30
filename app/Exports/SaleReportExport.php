<?php

namespace App\Exports;

use App\Models\SaleReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SaleReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = SaleReport::query();

        if (!empty($this->filters['q'])) {
            $q = $this->filters['q'];
            $query->where(function ($qq) use ($q) {
                $qq->where('invoice', 'like', "%{$q}%")
                    ->orWhere('order_no', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('part_no', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('invoice_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'qtr',
            'month',
            'fy_year',
            'invoice',
            'invoice_date',
            'order_no',
            'customer_name',
            'branch',
            'description',
            'part_no',
            'category',
            'principal_name',
            'authorised',
            'qty',
            'amount',
        ];
    }

    public function map($report): array
    {
        return [
            $report->qtr,
            $report->month,
            $report->fy_year,
            $report->invoice,
            optional($report->invoice_date)->format('Y-m-d'),
            $report->order_no,
            $report->customer_name,
            $report->branch,
            $report->description,
            $report->part_no,
            $report->category,
            $report->principal_name,
            $report->authorised,
            $report->qty,
            $report->amount,
        ];
    }
}
