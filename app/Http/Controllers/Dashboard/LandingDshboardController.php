<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingDshboardController extends Controller
{

    public function __construct()
    {
    }

    public function todayOrdersKpi(Request $request)
    {
        try {

            # Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();

            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();


            # Get count
            $todayCount = DB::table('orders')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count();

            $yesterdayCount = DB::table('orders')
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
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
                : ($changePct >= 0 ? '+' . number_format($changePct, 2) . '% Increase By Yesterday' : number_format(abs($changePct), 2) . '% Decrease By Yesterday');

            # Payload
            $response = [
                'todays_order' => (int) $todayCount,
                'yesterday_order' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];


            # Return response
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

            # Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();

            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();


            # Get count
            $todayCount = DB::table('quotations')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count();

            $yesterdayCount = DB::table('quotations')
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
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
                : ($changePct >= 0 ? '+' . number_format($changePct, 2) . '% Increase By Yesterday' : number_format(abs($changePct), 2) . '% Decrease By Yesterday');

            # Payload
            $response = [
                'todays_quotation' => (int) $todayCount,
                'yesterday_quotation' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];

            # Return response
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
            # Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();
            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();

            # Unique principal
            $todayCount = DB::table('order_details')
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$todayStart, $todayEnd])
                ->distinct()
                ->count('order_details.principal_id');

            # Unique yesterdays principal
            $yesterdayCount = DB::table('order_details')
                ->join('orders', 'order_details.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$yesterdayStart, $yesterdayEnd])
                ->distinct()
                ->count('order_details.principal_id');

            # Growth calculation (nullable if yesterday = 0 and today > 0)
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
                    ? '+' . number_format($changePct, 2) . '% Increase By Yesterday'
                    : number_format(abs($changePct), 2) . '% Decrease By Yesterday');

            # Response
            $response = [
                'todays_principals' => (int) $todayCount,
                'yesterday_principals' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];

            # Return response
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
            # Date filter
            $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();
            $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
            $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();


            # Count partial orders created today (only those with partial_order_status = 1 by default)
            $todayCount = DB::table('partial_orders')
                ->join('partial_order_details', 'partial_order_details.partial_order_id', '=', 'partial_orders.id')
                ->where('partial_order_details.partial_order_status', 1)
                ->whereBetween('partial_orders.created_at', [$todayStart, $todayEnd])
                ->distinct('partial_orders.id')
                ->count('partial_orders.id');

            # Count partial orders created yesterday
            $yesterdayCount = DB::table('partial_orders')
                ->where('partial_order_status', 1)
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->count();


            # compute change pct (nullable when yesterday = 0 and today > 0)
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
                    ? '+' . number_format($changePct, 2) . '% Increase By Yesterday'
                    : number_format(abs($changePct), 2) . '% Decrease By Yesterday');

            # Response
            $response = [
                'todays_partial_orders' => (int) $todayCount,
                'yesterday_partial_orders' => (int) $yesterdayCount,
                'change_pct' => $changePct,
                'label' => $label,
            ];

            # Return response
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


}