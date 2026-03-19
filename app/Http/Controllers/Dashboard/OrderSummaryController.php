<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderDetails;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderSummaryController extends Controller
{
    public function principalWiseOrder(Request $request)
    {
        try {
            // Request data
            $data = $request->only([
                'search',
                'start_date',
                'end_date',
                'per_page',
                'page',
            ]);

            // Init page details
            $search = $data['search'] ?? null;
            $type = $data['type'] ?? null;
            $from = $data['start_date'] ?? null;
            $to = $data['end_date'] ?? null;
            $perPage = $data['per_page'] ?? 15;

            // Group orders by Principal + created_at date
            $q = OrderDetails::query()
                ->selectRaw('principals.id as principal_id, principals.type as principal_name')
                ->selectRaw('COALESCE(SUM(order_details.total),0) as total_amount')
                ->selectRaw('COUNT(order_details.id) as orders_count')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('principals', 'principals.id', '=', 'order_details.principal_id')
                ->when($search, fn ($q) => $q->where('principals.type', 'like', "%{$search}%"))
                ->when($type, fn ($q) => $q->where('order_details.type', $type))
                ->when($from, fn ($q) => $q->whereDate('order_details.created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('order_details.created_at', '<=', $to))
                ->when(Utility::checkViewPermission('order_summary'), fn ($q) => $q->where('order_details.user_id', Auth::id()))
                ->groupBy('principals.id', 'principals.type')
                ->orderByDesc('type');

            // Return response
            $response = $q->paginate($perPage);

            return Utility::apiSuccess('List principal wise order', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error principalWiseOrder', ['exception' => $ex->getMessage()]);
        }
    }

    public function companyWiseOrder(Request $request)
    {
        try {
            // Request data
            $data = $request->only([
                'search',
                'start_date',
                'end_date',
                'per_page',
                'page',
            ]);

            // Init page details
            $perPage = $data['per_page'] ?? 15;

            // Build eloquent query
            $query = OrderDetails::query()
                ->selectRaw('customers.id as customer_id, customers.customer_name')
                ->selectRaw('COALESCE(SUM(order_details.total),0) as total')
                ->selectRaw('COUNT(order_details.id) as orders_count')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('customers', 'customers.id', '=', 'orders.company_id')
                ->when($data['search'] ?? null, fn ($q, $v) => $q->where('customers.customer_name', 'like', "%{$v}%"))
                ->when($data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('order_details.created_at', '>=', $v))
                ->when($data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('order_details.created_at', '<=', $v))
                ->when(Utility::checkViewPermission('order_summary'), fn ($q) => $q->where('order_details.user_id', Auth::id()))
                ->groupBy('customers.id', 'customers.customer_name')
                ->orderByDesc('customer_name');

            // Return response
            $response = $query->paginate($perPage);

            return Utility::apiSuccess('Company totals fetched', $response, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error principalWiseOrder', ['exception' => $ex->getMessage()]);
        }
    }

    public function branchWiseOrders(Request $request)
    {
        try {

            // Request  fields
            $data = $request->only([
                'search',
                'start_date',
                'end_date',
            ]);

            // Date fileds
            $from = $data['start_date'] ?? null;
            $to = $data['end_date'] ?? null;

            // Date filter for orderDetails
            $detailFilter = function ($q) use ($from, $to) {
                if ($from) {
                    $q->whereDate('order_details.created_at', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('order_details.created_at', '<=', $to);
                }

                if (Utility::checkViewPermission('order_summary')) {
                    $q->where('order_details.user_id', Auth::id());
                }
            };

            // Get all branches with sum of amounts
            $rows = Branch::query()
                ->select(['id', 'name'])
                ->when($data['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                ->withSum(['orderDetails as value' => $detailFilter], 'total')
                ->orderBy('name', 'asc')
                ->get();

            // Build chart data
            $categories = [];
            $seriesData = [];

            foreach ($rows as $r) {
                $categories[] = substr((string) $r->name, 0, 3);
                $seriesData[] = (float) $r->value;
            }

            // Y-axis max
            $max = $seriesData ? max($seriesData) : 0;
            $yMax = $this->niceCeil($max);

            // Payload
            $payload = [
                'data' => $seriesData,
                'xaxis' => $categories,
                'yaxis' => ['min' => 0, 'max' => $yMax, 'tickAmount' => 5],
            ];

            // Return response
            return Utility::apiSuccess('Branch wise orders list', $payload, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error branchWiseOrders', ['exception' => $ex->getMessage()]);
        }
    }

    public function statusWiseOrders(Request $request)
    {
        try {
            // Request fields
            $data = $request->only(['date_from', 'date_to']);

            // Get Partial query
            $partialSub = OrderDetails::query()
                ->select('order_id')
                ->where('partial_order_status', '<>', 1)
                ->groupBy('order_id');

            // Order query
            $row = Order::query()
                ->leftJoinSub($partialSub, 'partial_orders', function ($join) {
                    $join->on('partial_orders.order_id', '=', 'orders.id');
                })
                ->when($data['date_from'] ?? null, fn ($q) => $q->whereDate('orders.created_at', '>=', $data['date_from']))
                ->when($data['date_to'] ?? null, fn ($q) => $q->whereDate('orders.created_at', '<=', $data['date_to']))
                ->selectRaw("
                SUM(CASE WHEN orders.is_order_closed = '1' THEN 1 ELSE 0 END) AS close_count,
                SUM(CASE WHEN orders.is_order_closed = '0' AND partial_orders.order_id IS NOT NULL THEN 1 ELSE 0 END) AS partial_count,
                SUM(CASE WHEN orders.is_order_closed = '0' AND partial_orders.order_id IS NULL AND orders.is_shipment_pending = 1 THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN orders.is_shipment_pending = 0 THEN 1 ELSE 0 END) AS dispatched_count
            ")
                ->first();

            // Paylaod
            $payload = [
                'series' => [
                    (int) ($row->pending_count ?? 0),
                    (int) ($row->partial_count ?? 0),
                    (int) ($row->dispatched_count ?? 0),
                    (int) ($row->close_count ?? 0),
                ],
            ];

            // Return response
            return Utility::apiSuccess('Order status list', $payload, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error branchWiseOrders', ['exception' => $ex->getMessage()]);
        }
    }

    public function pendingDispatchReasons(Request $request)
    {
        try {
            // Request fields
            $data = $request->only(['date_from', 'date_to']);

            // Date filter
            $from = $data['date_from'] ?? null;
            $to = $data['date_to'] ?? null;

            // Get data
            $rows = Order::query()
                ->from('orders as o')
                ->join('pending_quotations as pq', 'pq.unique_quotation_no', '=', 'o.unique_quotation_no')
                ->leftJoin('reasons as r', 'r.id', '=', 'pq.reason_status_id')
                ->when($from, fn ($q) => $q->whereDate('o.created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('o.created_at', '<=', $to))
                ->when(Utility::checkViewPermission('order_summary'), fn ($q) => $q->where('o.user_id', Auth::id()))
                ->where('o.is_order_closed', '0')
                ->where('o.is_shipment_pending', 1)
                ->selectRaw('COALESCE(NULLIF(TRIM(r.name), ""), "Unspecified") as reason_name')
                ->selectRaw('COUNT(DISTINCT o.id) as orders_count')
                ->groupBy('reason_name')
                ->orderByDesc('orders_count')
                ->get();

            // Format data
            $categories = $rows->pluck('reason_name')->values()->all();
            $series = $rows->pluck('orders_count')->map(fn ($n) => (int) $n)->values()->all();

            // Retun response
            return Utility::apiSuccess('Pending to dispatch reasons', [
                'categories' => $categories,
                'series' => $series,
                'total' => array_sum($series),
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error pendingDispatchReasons', ['exception' => $ex->getMessage()]);
        }
    }

    private static function niceCeil(float $x): float
    {
        if ($x <= 0) {
            return 0;
        }
        $pow = pow(10, max(0, floor(log10($x)) - 1));
        $steps = [1, 2, 5, 10];
        foreach ($steps as $s) {
            $t = ceil($x / ($s * $pow)) * $s * $pow;
            if ($t >= $x) {
                return $t;
            }
        }

        return ceil($x);
    }
}
