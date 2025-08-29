<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\PerformanceReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerformanceSummaryController extends Controller
{

    public function getSaleReport(Request $request)
    {
        // validate query params
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sort_by' => [
                'nullable',
                Rule::in([
                    'invoice_date',
                    'invoice',
                    'customer_name',
                    'branch',
                    'principal_name',
                    'category',
                    'amount',
                    'qty',
                    'fy_year',
                    'month'
                ])
            ],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],

            // filters
            'q' => ['nullable', 'string', 'max:100'], // free text
            'fy_year' => ['nullable', 'string', 'max:16'],
            'month' => ['nullable', 'string', 'max:16'],
            'branch' => ['nullable', 'string', 'max:128'],
            'principal' => ['nullable', 'string', 'max:128'],
            'category' => ['nullable', 'string', 'max:128'],
            'authorised' => ['nullable', 'string', 'max:64'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 15);
        $sortBy = $data['sort_by'] ?? 'invoice_date';
        $sortDir = $data['sort_dir'] ?? 'desc';

        $query = PerformanceReport::query();

        // full-text-ish search (cheap and cheerful)
        if (!empty($data['q'])) {
            $q = $data['q'];
            $query->where(function ($qq) use ($q) {
                $qq->where('invoice', 'like', "%{$q}%")
                    ->orWhere('order_no', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('part_no', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // structured filters
        $query->when($data['fy_year'] ?? null, fn($q, $v) => $q->where('fy_year', $v))
            ->when($data['month'] ?? null, fn($q, $v) => $q->where('month', $v))
            ->when($data['branch'] ?? null, fn($q, $v) => $q->where('branch', $v))
            ->when($data['principal'] ?? null, fn($q, $v) => $q->where('principal_name', $v))
            ->when($data['category'] ?? null, fn($q, $v) => $q->where('category', $v))
            ->when($data['authorised'] ?? null, fn($q, $v) => $q->where('authorised', $v));

        // date range (invoice_date)
        if (!empty($data['date_from'])) {
            $query->whereDate('invoice_date', '>=', $data['date_from']);
        }
        if (!empty($data['date_to'])) {
            $query->whereDate('invoice_date', '<=', $data['date_to']);
        }

        $query->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc');

        $records = $query->paginate($perPage);

        return Utility::apiSuccess('Quotation status summary', $records, 200);

    }
}
