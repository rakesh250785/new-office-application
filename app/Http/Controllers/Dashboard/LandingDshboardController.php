<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LandingDshboardController extends Controller
{
    public function __construct() {}

    public function todayOrdersKpi()
    {
        try {

            // Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();

            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();

            // Get count
            $authUser = Auth::user();

            $todayCount = DB::table('orders')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->count();

            $yesterdayCount = DB::table('orders')
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->count();

            $changePct = null;
            if ($yesterdayCount > 0) {
                $changePct = round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 2);
            } elseif ($todayCount > 0 && $yesterdayCount === 0) {
                $changePct = null;
            } else {
                $changePct = 0.00;
            }

            $label = $changePct === null
                ? 'New from yesterday'
                : ($changePct >= 0 ? '+'.number_format($changePct, 2).'% Increase By Yesterday' : number_format(abs($changePct), 2).'% Decrease By Yesterday');

            // Payload
            $response = [
                'todays_order' => (int) $todayCount,
                'yesterday_order' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];

            // Return response
            return Utility::apiSuccess('TodayOrdersKpi', $response, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('TodayOrdersKpi', [
                'status' => false,
                'code' => 500,
                'message' => 'Error computing today orders KPI',
                'error' => $ex->getMessage(),
            ], 500);

        }
    }

    public function todayQuotationKpi(Request $request)
    {
        try {

            // Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();

            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();

            // Get count
            $authUser = Auth::user();

            $todayCount = DB::table('quotations')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->count();

            $yesterdayCount = DB::table('quotations')
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->count();

            $changePct = null;
            if ($yesterdayCount > 0) {
                $changePct = round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 2);
            } elseif ($todayCount > 0 && $yesterdayCount === 0) {
                $changePct = null;
            } else {
                $changePct = 0.00;
            }

            $label = $changePct === null
                ? 'New from yesterday'
                : ($changePct >= 0 ? '+'.number_format($changePct, 2).'% Increase By Yesterday' : number_format(abs($changePct), 2).'% Decrease By Yesterday');

            // Payload
            $response = [
                'todays_quotation' => (int) $todayCount,
                'yesterday_quotation' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];

            // Return response
            return Utility::apiSuccess('TodayQuotationsKpi', $response, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('TodayQuotationsKpi', [
                'status' => false,
                'code' => 500,
                'message' => 'Error computing today quotations KPI',
                'error' => $ex->getMessage(),
            ], 500);

        }
    }

    public function todayPrincipalOrdersKpi(Request $request)
    {
        try {
            // Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();
            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();

            // Unique principal
            $authUser = Auth::user();

            $todayCount = DB::table('order_details')
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$todayStart, $todayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->distinct()
                ->count('order_details.principal_id');

            // Unique yesterdays principal
            $yesterdayCount = DB::table('order_details')
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$yesterdayStart, $yesterdayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->distinct()
                ->count('order_details.principal_id');

            $changePct = null;
            if ($yesterdayCount > 0) {
                $changePct = round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 2);
            } elseif ($todayCount > 0 && $yesterdayCount === 0) {
                $changePct = null;
            } else {
                $changePct = 0.00;
            }

            $label = $changePct === null
                ? 'New from yesterday'
                : ($changePct >= 0
                    ? '+'.number_format($changePct, 2).'% Increase By Yesterday'
                    : number_format(abs($changePct), 2).'% Decrease By Yesterday');

            // Response
            $response = [
                'todays_principals' => (int) $todayCount,
                'yesterday_principals' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];

            // Return response
            return Utility::apiSuccess('TodayPrincipalOrdersKpi', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('TodayPrincipalOrdersKpi', [
                'status' => false,
                'code' => 500,
                'message' => 'Error computing principal orders KPI',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }

    public function todayPartialOrdersKpi(Request $request)
    {
        try {
            // Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();
            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();

            $authUser = Auth::user();

            $todayCount = DB::table('partial_orders')
                ->join('partial_order_details', 'partial_order_details.partial_order_id', '=', 'partial_orders.id')
                ->where('partial_order_details.partial_order_status', 0)
                ->whereBetween('partial_orders.created_at', [$todayStart, $todayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->distinct('partial_orders.id')
                ->count('partial_orders.id');

            // Count partial orders created yesterday
            $yesterdayCount = DB::table('partial_orders')
                ->where('partial_order_status', 1)
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->count();

            // compute change pct (nullable when yesterday = 0 and today > 0)
            $changePct = null;
            if ($yesterdayCount > 0) {
                $changePct = round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 2);
            } elseif ($todayCount > 0 && $yesterdayCount === 0) {
                $changePct = null;
            } else {
                $changePct = 0.00;
            }

            $label = $changePct === null
                ? 'New from yesterday'
                : ($changePct >= 0
                    ? '+'.number_format($changePct, 2).'% Increase By Yesterday'
                    : number_format(abs($changePct), 2).'% Decrease By Yesterday');

            // Response
            $response = [
                'todays_partial_orders' => (int) $todayCount,
                'yesterday_partial_orders' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];

            // Return response
            return Utility::apiSuccess('TodayPartialOrdersKpi', $response, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('TodayPartialOrdersKpi', [
                'status' => false,
                'code' => 500,
                'message' => 'Error computing today partial orders KPI',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }

    public function topCompaniesByWeekday(Request $request)
    {
        try {

            // Limit
            $limit = (int) $request->input('limit', 7);
            $now = Carbon::now();

            // Date filter
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();
            $authUser = Auth::user();

            // Fetch counts grouped by company_id and weekday (DAYOFWEEK: 1=Sun,2=Mon,...7=Sat)
            $rows = DB::table('orders')
                ->select(
                    'orders.company_id',
                    DB::raw('DAYOFWEEK(orders.created_at) AS dow'),
                    DB::raw('COUNT(*) AS cnt')
                )
                ->whereBetween('orders.created_at', [$from, $to])
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->groupBy('orders.company_id', 'dow')
                ->get();

            if ($rows->isEmpty()) {
                return Utility::apiSuccess('TopCompaniesByWeekday', [
                    'categories' => ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
                    'series' => [],
                ], 200);
            }

            // Aggregate into [company_id => [dow => cnt, ...], total => totalCount]
            $companies = [];
            foreach ($rows as $r) {
                $cid = (int) $r->company_id;
                $dow = (int) $r->dow;
                $cnt = (int) $r->cnt;

                if (! isset($companies[$cid])) {
                    $companies[$cid] = ['tot' => 0, 'by_dow' => array_fill(1, 7, 0)];
                }
                $companies[$cid]['by_dow'][$dow] = $cnt;
                $companies[$cid]['tot'] += $cnt;
            }

            // Sort companies by total desc and pick top N
            uasort($companies, function ($a, $b) {
                return $b['tot'] <=> $a['tot'];
            });
            $top = array_slice($companies, 0, $limit, true);

            // Get company names
            $companyIds = array_keys($top);
            $names = DB::table('customers')
                ->whereIn('id', $companyIds ?: [0])
                ->pluck('company_name', 'id')
                ->toArray();

            // Convert to Apex-friendly series: data must be Mon..Sun order.
            // DAYOFWEEK: 1=Sun,2=Mon,...7=Sat -> map to Mon..Sun indices
            $series = [];
            foreach ($top as $cid => $data) {
                $byDow = $data['by_dow'];
                $mon = $byDow[2] ?? 0;
                $tue = $byDow[3] ?? 0;
                $wed = $byDow[4] ?? 0;
                $thu = $byDow[5] ?? 0;
                $fri = $byDow[6] ?? 0;
                $sat = $byDow[7] ?? 0;
                $sun = $byDow[1] ?? 0;

                $series[] = [
                    'name' => $names[$cid] ?? 'Unknown',
                    'data' => [$mon, $tue, $wed, $thu, $fri, $sat, $sun],
                    'total' => $data['tot'],
                ];
            }

            // Optionally return top N list and categories
            $response = [
                'categories' => ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
                'series' => $series,
            ];

            // Return api response
            return Utility::apiSuccess('TopCompaniesByWeekday', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('TopCompaniesByWeekday', [
                'status' => false,
                'code' => 500,
                'message' => 'Error computing top companies by weekday',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }

    public function topPrincipalsMonthWise(Request $request)
    {
        try {
            $now = Carbon::now();
            $year = (int) $now->year;

            $months = [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ];

            $counts = [];
            $topPrincipalNames = [];
            $authUser = Auth::user();

            for ($m = 1; $m <= 12; $m++) {
                $top = DB::table('order_details')
                    ->join('orders', 'order_details.order_id', '=', 'orders.id')
                    ->leftJoin('principals', 'order_details.principal_id', '=', 'principals.id')
                    ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                        $query->where('branch_id', $authUser->branch_id)
                            ->where('user_id', $authUser->id);
                    })
                    ->select(
                        'order_details.principal_id',
                        DB::raw('COALESCE(principals.type, principals.type) as principal_name'),
                        DB::raw('COUNT(*) as cnt')
                    )
                    ->whereYear('orders.created_at', $year)
                    ->whereMonth('orders.created_at', $m)
                    ->groupBy('order_details.principal_id', 'principal_name')
                    ->orderByDesc('cnt')
                    ->first();

                if ($top) {
                    $counts[] = (int) $top->cnt;
                    $topPrincipalNames[] = $top->principal_name ?? ('Principal '.$top->principal_id);
                } else {
                    $counts[] = 0;
                    $topPrincipalNames[] = null;
                }
            }

            $response = [
                'categories' => $months,
                'series' => [
                    [
                        'name' => 'Top Principal Orders',
                        'data' => $counts,
                    ],
                ],
                'top_principals' => $topPrincipalNames,
            ];

            return Utility::apiSuccess('TopPrincipalsMonthWise', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('TopPrincipalsMonthWise', [
                'status' => false,
                'code' => 500,
                'message' => 'Error computing top principals month-wise',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }

    public function topProductsCurrentMonth(Request $request)
    {
        try {

            // Limit and date filter
            $limit = (int) $request->input('limit', 5);
            $now = Carbon::now();
            $year = (int) $now->year;
            $month = (int) $now->month;
            $authUser = Auth::user();

            // Order deatails
            $rows = DB::table('order_details')
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->select('order_details.product_id', DB::raw('COALESCE(SUM(order_details.total), 0) as total'))
                ->whereYear('orders.created_at', $year)
                ->whereMonth('orders.created_at', $month)
                ->when(Auth::user()?->role?->name != 'admin', function ($query) use ($authUser) {
                    $query->where('branch_id', $authUser->branch_id)
                        ->where('user_id', $authUser->id);
                })
                ->groupBy('order_details.product_id')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();

            if ($rows->isEmpty()) {
                return Utility::apiSuccess('TopProductsCurrentMonth', [
                    'labels' => [],
                    'series' => [],
                    'totals' => [],
                    'percentages' => [],
                ], 200);
            }

            // product id
            $productIds = $rows->pluck('product_id')->toArray();
            $names = DB::table('products')
                ->whereIn('id', $productIds)
                ->pluck('part_no', 'id')
                ->toArray();

            $labels = [];
            $series = [];
            $totals = [];

            foreach ($rows as $r) {
                $pid = (int) $r->product_id;
                $amount = (int) $r->total;
                $labels[] = $names[$pid] ?? ('Product '.$pid);
                $series[] = $amount;
                $totals[] = ['product_id' => $pid, 'name' => $names[$pid] ?? ('Product '.$pid), 'value' => $amount];
            }

            $sum = array_sum($series);
            $percentages = array_map(function ($v) use ($sum) {
                return $sum > 0 ? round(($v / $sum) * 100, 2) : 0;
            }, $series);

            // Response
            $response = [
                'labels' => $labels,
                'series' => $series,
                'percentages' => $percentages,
                'totals' => $totals,
            ];

            // Return response
            return Utility::apiSuccess('TopProductsCurrentMonth', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('TopProductsCurrentMonth', [
                'status' => false,
                'code' => 500,
                'message' => 'Error computing top products current month',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }
}
