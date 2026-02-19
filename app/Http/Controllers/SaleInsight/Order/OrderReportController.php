<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
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
            # Accept the filters
            $params = $request->only([
                'branch_list',
                'owner_list',
                'currency_list',
                'principal_list',
                'start_date',
                'end_date',
                'search',
                'per_page',
                'page'
            ]);

            # Validation rules
            $validator = Validator::make($params, [
                'branch_list' => 'nullable|array',
                'branch_list.*' => 'integer',
                'owner_list' => 'nullable|array',
                'owner_list.*' => 'integer',
                'currency_list' => 'nullable|array',
                'currency_list.*' => 'integer',
                'principal_list' => 'nullable|array',
                'principal_list.*' => 'integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'date_range' => 'nullable|string',
                'search' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
            ]);

            # Return validation return
            if ($validator->fails()) {
                return Utility::apiError('Invalid filters', $validator->errors(), 422);
            }

            # Build base query
            $query = Order::with(['customer', 'details', 'quotation', 'owner'])
                ->whereNull('deleted_at')
                ->orderByDesc('id');

            # Apply filters (arrays are expected from frontend; cast to array to be safe)
            if (!empty($params['branch_list'])) {
                $query->where('branch_id',  $params['branch_list']);
            }

            if (!empty($params['owner_list'])) {
                $query->where('owner_id', $params['owner_list']);
            }

            if (!empty($params['currency_list'])) {
                $query->where('currency_id', $params['currency_list']);
            }

            if (!empty($params['principal_list'])) {
                $query->whereHas('details', function ($q) use ($params) {
                    $q->where('principal_id', $params['principal_list']);
                });
            }

            # Date range handling:
            if (!empty($params['start_date']) && !empty($params['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($params['start_date'])->startOfDay(),
                    Carbon::parse($params['end_date'])->endOfDay()
                ]);
            }

            if (!empty($params['search'])) {
                $search = trim($params['search']);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customer', function ($qc) use ($search) {
                        $qc->where('customer_name', 'like', "%{$search}%");
                    })
                        ->orWhere('unique_order_no', 'like', "%{$search}%")
                        ->orWhere('customer_order_no', 'like', "%{$search}%")
                        ->orWhere('lead_from', 'like', "%{$search}%");
                });
            }

            # Pagination
            $perPage = $params['per_page'] ?? config('constant.per_page', 10);
            $result = $query->paginate($perPage);

            return Utility::apiSuccess('Order report fetched', $result, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed fetching order report', ['exception' => $ex->getMessage()], 500);
        }
    }

}
