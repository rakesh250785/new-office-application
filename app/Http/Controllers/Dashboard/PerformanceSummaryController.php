<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\SaleReportExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\SaleReport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SaleReportImport;
use Illuminate\Support\Facades\Log;

class PerformanceSummaryController extends Controller
{

    public function getSaleReport(Request $request)
    {
        try {
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

                'q' => ['nullable', 'string', 'max:100'],
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

            $query = SaleReport::query();
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

            $query->when($data['fy_year'] ?? null, fn($q, $v) => $q->where('fy_year', $v))
                ->when($data['month'] ?? null, fn($q, $v) => $q->where('month', $v))
                ->when($data['branch'] ?? null, fn($q, $v) => $q->where('branch', $v))
                ->when($data['principal'] ?? null, fn($q, $v) => $q->where('principal_name', $v))
                ->when($data['category'] ?? null, fn($q, $v) => $q->where('category', $v))
                ->when($data['authorised'] ?? null, fn($q, $v) => $q->where('authorised', $v));

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
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error quotationStatusReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function importSaleData(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
            ]);
            Excel::import(new SaleReportImport, $request->file('file'));
            return Utility::apiSuccess('File imported successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error quotationStatusReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function exportSaleData(Request $request)
    {
        try {
            $filters = $request->only(['q', 'date_from', 'date_to']);
            $fileName = 'performance_reports_' . now()->format('Ymd_His') . '.xlsx';
            return Excel::download(new SaleReportExport($filters), $fileName);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error quotationStatusReport', ['exception' => $ex->getMessage()]);
        }
    }


    public function branchSummaryReport(Request $request)
    {
        try {
            $years = $request->input('years');

            if (empty($years) || !is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2) . '-' . $fyStart - 1,
                    $fyStart - 1 . '-' . ($fyStart - 1 + 1),
                    ($fyStart) . '-' . ($fyStart + 1),
                ];
            }

            $selects = ['branch'];
            foreach ($years as $y) {
                $selects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }

            $rows = SaleReport::query()
                ->selectRaw(implode(", ", $selects))
                ->groupBy('branch')
                ->orderBy('branch')
                ->get();

            $summary = $rows->map(function ($row) use ($years) {
                $rowArr = $row->toArray();
                $count = count($years);

                if ($count >= 2) {
                    $prev = $rowArr[$years[$count - 2]] ?? 0;
                    $curr = $rowArr[$years[$count - 1]] ?? 0;
                    $rowArr['growth'] = $prev > 0
                        ? round((($curr - $prev) / $prev) * 100, 2)
                        : null;
                } else {
                    $rowArr['growth'] = null;
                }

                return $rowArr;
            });

            $totals = ['branch' => 'Total'];
            foreach ($years as $y) {
                $totals[$y] = $summary->sum($y);
            }

            if (count($years) >= 2) {
                $prevTotal = $totals[$years[count($years) - 2]] ?? 0;
                $currTotal = $totals[$years[count($years) - 1]] ?? 0;
                $totals['growth'] = $prevTotal > 0
                    ? round((($currTotal - $prevTotal) / $prevTotal) * 100, 2)
                    : null;
            } else {
                $totals['growth'] = null;
            }

            $summary->push($totals);

            $headers = array_merge(['branch'], $years, ['growth']);

            return Utility::apiSuccess('Branch summary report', [
                'headers' => $headers,
                'data' => $summary->values(),
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error branchSummaryReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function principalSummaryReport(Request $request)
    {
        try {
            $years = $request->input('years');

            if (empty($years) || !is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2) . '-' . $fyStart - 1,
                    $fyStart - 1 . '-' . ($fyStart - 1 + 1),
                    ($fyStart) . '-' . ($fyStart + 1),
                ];
            }

            $selects = ['principal_name as principal'];
            foreach ($years as $y) {
                $selects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }

            $rows = SaleReport::query()
                ->selectRaw(implode(", ", $selects))
                ->groupBy('principal_name')
                ->orderBy('principal_name')
                ->get();

            $summary = $rows->map(function ($row) use ($years) {
                $rowArr = $row->toArray();
                $count = count($years);

                if ($count >= 2) {
                    $prev = $rowArr[$years[$count - 2]] ?? 0;
                    $curr = $rowArr[$years[$count - 1]] ?? 0;
                    $rowArr['growth'] = $prev > 0
                        ? round((($curr - $prev) / $prev) * 100, 2)
                        : null;
                } else {
                    $rowArr['growth'] = null;
                }

                return $rowArr;
            });

            $totals = ['principal' => 'Total'];
            foreach ($years as $y) {
                $totals[$y] = $summary->sum($y);
            }

            if (count($years) >= 2) {
                $prevTotal = $totals[$years[count($years) - 2]] ?? 0;
                $currTotal = $totals[$years[count($years) - 1]] ?? 0;
                $totals['growth'] = $prevTotal > 0
                    ? round((($currTotal - $prevTotal) / $prevTotal) * 100, 2)
                    : null;
            } else {
                $totals['growth'] = null;
            }

            $summary->push($totals);

            $headers = array_merge(['principal'], $years, ['growth']);

            return Utility::apiSuccess('Principal summary report', [
                'headers' => $headers,
                'data' => $summary->values(),
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error principalSummaryReport', ['exception' => $ex->getMessage()]);
        }
    }

    
}
