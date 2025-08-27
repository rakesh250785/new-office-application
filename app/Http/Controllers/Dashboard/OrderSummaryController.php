<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OrderDetails;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class OrderSummaryController extends Controller
{
    public function principalWiseOrder(Request $request)
    {
        try {
            # Request data
            $data = $request->only([
                'search',
                'start_date',
                'end_date',
                'per_page',
                'page',
            ]);

            # Init page details
            $search = $data['search'] ?? null;
            $type = $data['type'] ?? null;
            $from = $data['start_date'] ?? null;
            $to = $data['end_date'] ?? null;
            $perPage = $data['per_page'] ?? 15;

            # Group orders by Principal + created_at date
            $q = OrderDetails::query()
                ->selectRaw('principals.id as principal_id, principals.type as principal_name')
                ->selectRaw('COALESCE(SUM(order_details.total),0) as total_amount')
                ->selectRaw('COUNT(order_details.id) as orders_count')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('principals', 'principals.id', '=', 'order_details.principal_id')
                ->when($search, fn($q) => $q->where('principals.type', 'like', "%{$search}%"))
                ->when($type, fn($q) => $q->where('order_details.type', $type))
                ->when($from, fn($q) => $q->whereDate('order_details.created_at', '>=', $from))
                ->when($to, fn($q) => $q->whereDate('order_details.created_at', '<=', $to))
                ->groupBy('principals.id', 'principals.type')
                ->orderByDesc('type');

            # Return response
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
            # Request data
            $data = $request->only([
                'search',
                'start_date',
                'end_date',
                'per_page',
                'page',
            ]);

            # Init page details
            $perPage = $data['per_page'] ?? 15;

            # Build eloquent query
            $query = OrderDetails::query()
                ->selectRaw('customers.id as customer_id, customers.customer_name')
                ->selectRaw('COALESCE(SUM(order_details.total),0) as total')
                ->selectRaw('COUNT(order_details.id) as orders_count')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('customers', 'customers.id', '=', 'orders.company_id')
                ->when($data['search'] ?? null, fn($q, $v) => $q->where('customers.customer_name', 'like', "%{$v}%"))
                ->when($data['date_from'] ?? null, fn($q, $v) => $q->whereDate('order_details.created_at', '>=', $v))
                ->when($data['date_to'] ?? null, fn($q, $v) => $q->whereDate('order_details.created_at', '<=', $v))
                ->groupBy('customers.id', 'customers.customer_name')
                ->orderByDesc('customer_name');

            # Return response
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

            # Request  fields
            $data = $request->only([
                'search',
                'start_date',
                'end_date',
            ]);

            # Date fileds
            $from = $data['start_date'] ?? null;
            $to = $data['end_date'] ?? null;

            # Date filter for orderDetails
            $detailFilter = function ($q) use ($from, $to) {
                if ($from)
                    $q->whereDate('order_details.created_at', '>=', $from);
                if ($to)
                    $q->whereDate('order_details.created_at', '<=', $to);
            };

            # Get all branches with sum of amounts
            $rows = Branch::query()
                ->select(['id', 'name'])
                ->when($data['search'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
                ->withSum(['orderDetails as value' => $detailFilter], 'total')
                ->orderBy('name', 'asc')
                ->get();

            # Build chart data
            $categories = [];
            $seriesData = [];

            foreach ($rows as $r) {
                $categories[] = substr((string) $r->name, 0, 3);
                $seriesData[] = (float) $r->value;
            }

            # Y-axis max
            $max = $seriesData ? max($seriesData) : 0;
            $yMax = $this->niceCeil($max);

            # Payload
            $payload = [
                'data' => $seriesData,
                'xaxis' => $categories,
                'yaxis' => ['min' => 0, 'max' => $yMax, 'tickAmount' => 5],
            ];

            # Return response
            return Utility::apiSuccess('Branch wise orders list', $payload, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error branchWiseOrders', ['exception' => $ex->getMessage()]);
        }
    }

    private static function niceCeil(float $x): float
    {
        if ($x <= 0)
            return 0;
        $pow = pow(10, max(0, floor(log10($x)) - 1));
        $steps = [1, 2, 5, 10];
        foreach ($steps as $s) {
            $t = ceil($x / ($s * $pow)) * $s * $pow;
            if ($t >= $x)
                return $t;
        }
        return ceil($x);
    }
}
