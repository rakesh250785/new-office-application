<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\Utility;
use Exception;
class OrderReportController extends Controller
{
    public function __construct()
    {
    }
    public function getOrderReport(Request $request)
    {
        try {
            # Get specific fields
            $params = $request->only([
                'branch_id',
                'date_range',
                'search',
                'per_page',
                'page'
            ]);

            # Validation rule
            $validator = Validator::make($params, [
                'branch_id' => 'nullable|integer',
                'date_range' => 'nullable|string',
                'search' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Invalid filters', $validator->errors(), 422);
            }

            # Get order report
            $query = Order::with(['customer', 'details', 'quotation', 'owner'])
                ->whereNull('deleted_at')
                ->orderByDesc('id');

            # Get permission based branch filter
            if (!Auth::user()->hasPermission('branch_all')) {
                $query->where('in_branch_id', Auth::user()->branch_id);
            }

            # Filter branch
            if (!empty($params['branch_id'])) {
                $query->where('in_branch_id', $params['branch_id']);
            }

            # Filter date range
            if (!empty($params['date_range'])) {
                [$from, $to] = explode('|', $params['date_range']);
                $query->whereBetween('dt_created', [
                    date('Y-m-d', strtotime($from)),
                    date('Y-m-d', strtotime($to . ' +1 day')),
                ]);
            }

            # Filter search term
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customer', function ($qc) use ($search) {
                        $qc->where('name', 'like', "%{$search}%");
                    })->orWhere('unique_order_id', 'like', "%{$search}%")
                        ->orWhere('customer_order_no', 'like', "%{$search}%");
                });
            }

            # Define pagination
            $perPage = $params['per_page'] ?? 10;
            $result = $query->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Order report fetched', $result);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed fetching order report', ['exception' => $ex->getMessage()]);
        }
    }
}
