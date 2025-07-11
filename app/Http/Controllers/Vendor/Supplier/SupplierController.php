<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class SupplierController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateSupplier(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only([
                'part_no_search',
                'reference_date',
                'principal_id',
                'source_id',
                'currency_id',
                'rate',
                'factor',
                'total_cost',
                'discount',
                'net_cost',
                'profit',
                'selling_price',
                'branch_id',
                'decsription'
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'part_no_search' => 'required|string',
                'reference_date' => 'required|date',
                'principal_id' => 'required',
                'source_id' => 'required|array',
                'currency_id' => 'required|array',
                'rate' => 'required|array',
                'factor' => 'required|array',
                'total_cost' => 'required|array',
                'discount' => 'required|array',
                'net_cost' => 'required|array',
                'profit' => 'required|array',
                'selling_price' => 'required|array',
                'branch_id' => 'sometimes|numeric',
                'decsription' => 'sometimes|string',
                'date' => 'required|date',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Get existing supplier
            $partNo = $data['product_search'];
            $branchId = $data['branch_id'] ?? Auth::user()->branch_id;

            Supplier::where('s_partno', $partNo)
                ->where('branch_id', $branchId)
                ->delete();

            # Mass insert prepare
            $records = [];
            foreach ($data['source_id'] as $i => $source_id) {
                $records[] = [
                    'principal_id' => $data['principal_id'],
                    'part_no' => $partNo,
                    'description' => $data['decsription'] ?? '',
                    'source_id' => $source_id,
                    'currency_id' => $data['currency'][$i],
                    'rate' => $data['rate'][$i],
                    'factor' => $data['factor'][$i],
                    'total_cost' => round($data['total_cost'][$i]),
                    'discount' => $data['discount'][$i],
                    'net_price' => round($data['net_cost'][$i]),
                    'profit' => $data['profit'][$i],
                    'selling_price' => round($data['selling_price'][$i]),
                    'user_id' => Auth::id(),
                    'branch_id' => $branchId,
                    'deleted_at' => null,
                    'date' => isset($data['date'][$i])
                        ? Carbon::parse($data['date'][$i])->format('Y-m-d')
                        : Carbon::parse($data['reference_date'])->format('Y-m-d'),
                ];
            }

            # Insert data
            $status = Supplier::insert($records);

            # Return if fail
            if (!$status) {
                return Utility::apiError('Fail to insert supplier');
            }
            return Utility::apiSuccess('Supplier added successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error while saving supplier', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteSupplier(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['part_no']);

            # Validation rule
            $validator = Validator::make($data, [
                'part_no' => 'required|string'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete supplier
            $deleted = Supplier::where('part_no', $data['part_no'])->delete();

            # Return if fail to delete
            if (!$deleted) {
                return Utility::apiError('Failed to delete supplier', [], 400);
            }

            # Return response
            return Utility::apiSuccess('Supplier deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error while deleting supplier', ['exception' => $ex->getMessage()]);
        }
    }

    public function getSuppliers(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only([
                'page',
                'per_page',
                'principal_id',
                'part_no',
                'source_id',
                'currency_id'
            ]);

            # Set per page
            $perPage = $data['per_page'] ?? 10;

            # Get supplier
            $query = Supplier::whereNull('deleted_at')->orderByDesc('id');

            # Filter condition
            if (!empty($data['principal_id'])) {
                $query->where('principal_id', $data['principal_id']);
            }
            if (!empty($data['part_no'])) {
                $query->where('part_no', $data['part_no']);
            }
            if (!empty($data['source_id'])) {
                $query->where('source_id', $data['source_id']);
            }
            if (!empty($data['currency_id'])) {
                $query->where('currency_id', $data['currency_id']);
            }

            # Get result
            $suppliers = $query->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Supplier list fetched', $suppliers, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching supplier list', ['exception' => $ex->getMessage()]);
        }
    }
}
