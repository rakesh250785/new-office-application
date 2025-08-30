<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\SaleReportExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\SaleReport;
use DB;
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
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);

            // Default years
            if (empty($years) || !is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;

                $years = [
                    ($fyStart - 2) . '-' . ($fyStart - 1),
                    ($fyStart - 1) . '-' . ($fyStart),
                    ($fyStart) . '-' . ($fyStart + 1),
                ];
            }

            // Month & Quarter mapping
            $monthNames = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];
            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];
            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            // Select fields
            $selects = ['branch'];
            foreach ($years as $y) {
                $selects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }

            if (count($years) >= 2) {
                $prevYear = $years[count($years) - 2];
                $currYear = $years[count($years) - 1];
                $selects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $selects[] = "NULL as growth";
            }

            // Query with filters
            $query = DB::table('performance_reports');
            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && !empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            $rows = (clone $query)
                ->selectRaw(implode(", ", $selects))
                ->groupBy('branch')
                ->orderBy('branch')
                ->paginate($perPage);

            // Totals
            $totalSelects = ["'Total' as branch"];
            foreach ($years as $y) {
                $totalSelects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }
            if (count($years) >= 2) {
                $totalSelects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $totalSelects[] = "NULL as growth";
            }

            $totals = (clone $query)->selectRaw(implode(", ", $totalSelects))->first();
            $headers = array_merge(['branch'], $years, ['growth']);

            return Utility::apiSuccess('Branch summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
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
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);

            if (empty($years) || !is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;
                $years = [
                    ($fyStart - 2) . '-' . ($fyStart - 1),
                    ($fyStart - 1) . '-' . ($fyStart),
                    ($fyStart) . '-' . ($fyStart + 1),
                ];
            }

            $monthNames = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];
            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];
            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            $selects = ['principal_name as principal'];
            foreach ($years as $y) {
                $selects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }

            if (count($years) >= 2) {
                $prevYear = $years[count($years) - 2];
                $currYear = $years[count($years) - 1];
                $selects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $selects[] = "NULL as growth";
            }

            $query = DB::table('performance_reports');
            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && !empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            $rows = (clone $query)
                ->selectRaw(implode(", ", $selects))
                ->groupBy('principal_name')
                ->orderBy('principal_name')
                ->paginate($perPage);

            $totalSelects = ["'Total' as principal"];
            foreach ($years as $y) {
                $totalSelects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }
            if (count($years) >= 2) {
                $totalSelects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $totalSelects[] = "NULL as growth";
            }

            $totals = (clone $query)->selectRaw(implode(", ", $totalSelects))->first();
            $headers = array_merge(['principal'], $years, ['growth']);

            return Utility::apiSuccess('Principal summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error principalSummaryReport', ['exception' => $ex->getMessage()]);
        }
    }



    public function customerSummaryReport(Request $request)
    {
        try {
            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);

            if (empty($years) || !is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;
                $years = [
                    ($fyStart - 2) . '-' . ($fyStart - 1),
                    ($fyStart - 1) . '-' . ($fyStart),
                    ($fyStart) . '-' . ($fyStart + 1),
                ];
            }

            $monthNames = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];
            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];
            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            $selects = ['customer_name as customer'];
            foreach ($years as $y) {
                $selects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }

            if (count($years) >= 2) {
                $prevYear = $years[count($years) - 2];
                $currYear = $years[count($years) - 1];
                $selects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $selects[] = "NULL as growth";
            }

            $query = DB::table('performance_reports');
            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && !empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            $rows = (clone $query)
                ->selectRaw(implode(", ", $selects))
                ->groupBy('customer_name')
                ->orderBy('customer_name')
                ->paginate($perPage);

            $totalSelects = ["'Total' as customer"];
            foreach ($years as $y) {
                $totalSelects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }
            if (count($years) >= 2) {
                $totalSelects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $totalSelects[] = "NULL as growth";
            }

            $totals = (clone $query)->selectRaw(implode(", ", $totalSelects))->first();
            $headers = array_merge(['customer'], $years, ['growth']);

            return Utility::apiSuccess('Customer summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error customerSummaryReport', ['exception' => $ex->getMessage()]);
        }
    }


    public function categorySummaryReport(Request $request)
    {
        try {
            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);

            if (empty($years) || !is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;
                $years = [
                    ($fyStart - 2) . '-' . ($fyStart - 1),
                    ($fyStart - 1) . '-' . ($fyStart),
                    ($fyStart) . '-' . ($fyStart + 1),
                ];
            }

            $monthNames = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];
            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];
            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            $selects = ['category'];
            foreach ($years as $y) {
                $selects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }

            if (count($years) >= 2) {
                $prevYear = $years[count($years) - 2];
                $currYear = $years[count($years) - 1];
                $selects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $selects[] = "NULL as growth";
            }

            $query = DB::table('performance_reports');
            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && !empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            $rows = (clone $query)
                ->selectRaw(implode(", ", $selects))
                ->groupBy('category')
                ->orderBy('category')
                ->paginate($perPage);

            $totalSelects = ["'Total' as category"];
            foreach ($years as $y) {
                $totalSelects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }
            if (count($years) >= 2) {
                $totalSelects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $totalSelects[] = "NULL as growth";
            }

            $totals = (clone $query)->selectRaw(implode(", ", $totalSelects))->first();
            $headers = array_merge(['category'], $years, ['growth']);

            return Utility::apiSuccess('Category summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error categorySummaryReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function authorisedSummaryReport(Request $request)
    {
        try {
            $years = $request->input('years');
            $month = $request->input('month');
            $quarter = $request->input('quarter');
            $perPage = $request->input('per_page', 10);

            if (empty($years) || !is_array($years)) {
                $currentYear = now()->year;
                $fyStart = now()->month >= 4 ? $currentYear : $currentYear - 1;
                $years = [
                    ($fyStart - 2) . '-' . ($fyStart - 1),
                    ($fyStart - 1) . '-' . $fyStart,
                    $fyStart . '-' . ($fyStart + 1),
                ];
            }

            $monthNames = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];
            $quarterMap = [
                'Q1' => ['April', 'May', 'June'],
                'Q2' => ['July', 'August', 'September'],
                'Q3' => ['October', 'November', 'December'],
                'Q4' => ['January', 'February', 'March'],
            ];
            $quarterMonths = $quarter ? ($quarterMap[$quarter] ?? []) : [];

            $selects = ['authorised'];
            foreach ($years as $y) {
                $selects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }

            if (count($years) >= 2) {
                $prevYear = $years[count($years) - 2];
                $currYear = $years[count($years) - 1];
                $selects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $selects[] = "NULL as growth";
            }

            $query = DB::table('performance_reports');
            if ($month && isset($monthNames[(int) $month])) {
                $query->where('month', $monthNames[(int) $month]);
            } elseif ($quarter && !empty($quarterMonths)) {
                $query->whereIn('month', $quarterMonths);
            }

            $rows = (clone $query)
                ->selectRaw(implode(", ", $selects))
                ->groupBy('authorised')
                ->orderBy('authorised')
                ->paginate($perPage);

            $totalSelects = ["'Total' as authorised"];
            foreach ($years as $y) {
                $totalSelects[] = "SUM(CASE WHEN fy_year = '{$y}' THEN amount ELSE 0 END) as `{$y}`";
            }
            if (count($years) >= 2) {
                $totalSelects[] = "CASE 
                WHEN SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END) > 0
                THEN ROUND(((SUM(CASE WHEN fy_year = '{$currYear}' THEN amount ELSE 0 END) -
                             SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END))
                             / SUM(CASE WHEN fy_year = '{$prevYear}' THEN amount ELSE 0 END)) * 100, 2)
                ELSE NULL END as growth";
            } else {
                $totalSelects[] = "NULL as growth";
            }

            $totals = (clone $query)->selectRaw(implode(", ", $totalSelects))->first();

            $headers = array_merge(['authorised'], $years, ['growth']);

            return Utility::apiSuccess('Authorised summary report', [
                'headers' => $headers,
                'pagination' => $rows,
                'total' => $totals,
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error authorisedSummaryReport', ['exception' => $ex->getMessage()]);
        }
    }
}
