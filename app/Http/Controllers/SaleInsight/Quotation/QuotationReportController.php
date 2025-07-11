<?php

namespace App\Http\Controllers\SaleInsight\Quotation;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
class QuotationReportController extends Controller
{

    public function __construct()
    {
    }

    public function getQuotationReport(Request $request)
    {
        try {
            # Get requested fields
            $params = $request->only([
                'owner_id',
                'branch_id',
                'principal',
                'status',
                'date_range',
                'search',
                'per_page',
                'page'
            ]);

            # Validation rule
            $validator = Validator::make($params, [
                'owner_id' => 'nullable|integer|exists:owners,id',
                'branch_id' => 'nullable|integer',
                'principal' => 'nullable|string',
                'status' => 'nullable|integer|in:0,1,2,3',
                'date_range' => 'nullable|string',
                'search' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Invalid filters', $validator->errors(), 422);
            }

            # Get quotation
            $query = Quotation::with(['customer', 'details', 'owner'])
                ->orderByDesc('in_quot_id')
                ->whereNull('deleted_at');

            # Check branch permission
            if (!Auth::user()->hasPermission('branch_all')) {
                $query->where('in_branch_id', Auth::user()->branch_id);
            }

            # Filter owner id
            if (!empty($params['owner_id'])) {
                $query->where('owner_id', $params['owner_id']);
            }

            # Filter branch id
            if (!empty($params['branch_id'])) {
                $query->where('in_branch_id', $params['branch_id']);
            }

            # Filter principal id
            if (!empty($params['principal_id'])) {
                $query->whereHas('details', function ($q) use ($params) {
                    $q->where('id', $params['principal_id']);
                });
            }

            # Filter status
            if (isset($params['status'])) {
                $query->where('order_status', $params['status']);
            }

            # Date filter
            if (!empty($params['date_range'])) {
                [$from, $to] = explode('|', $params['date_range']);
                $query->whereBetween('dt_date_created', [
                    date('Y-m-d', strtotime($from)),
                    date('Y-m-d', strtotime($to . ' +1 day')),
                ]);
            }

            # Filter search term
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customer', function ($qc) use ($search) {
                        $qc->where('st_com_name', 'like', "%{$search}%");
                    })->orWhere('in_quot_num', 'like', "%{$search}%");
                });
            }

            # Define pagination
            $perPage = $params['per_page'] ?? 10;
            $result = $query->paginate($perPage);

            # Return result
            return Utility::apiSuccess('Quotation report fetched', $result);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed quotation report', ['exception' => $ex->getMessage()]);
        }
    }
}