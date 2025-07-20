<?php

namespace App\Http\Controllers\Vendor\Courier;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Helpers\Utility;
use App\Models\Courier;
use App\Models\Branch;
use Exception;
use Auth;

class CourierController extends Controller
{
    public function __construct()
    {
    }
    public function addUpdateCourier(Request $request)
    {
        try {

            # Get specific fields
            $data = $request->only(['courier_name', 'branch_name', 'branch_id', 'update_status', 'courier_id']);

            # Get branch id
            $branchId = Auth::user()['branch_id'] ?? $data['branch_id'] ?? null;

            # Validate fields
            $validator = Validator::make($data, [
                'courier_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('couriers', 'name')
                        ->where(fn($query) => $query->where('branch_id', $branchId ?? null))
                        ->ignore($data['courier_id'] ?? null),
                ],
                'branch_id' => 'nullable|integer|exists:branches,id',
                'courier_id' => 'nullable|integer|exists:couriers,id',
                'update_status' => 'nullable|sometimes|boolean'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Get branch name
            $branch = Branch::find($branchId);
            $branchCode = $branch['code'] ?? null;

            # Return if branch not found
            if (!$branchCode) {
                return Utility::apiSuccess('Branch not found', [], 221);
            }

            # Prepare input
            $addUpdate = [
                'name' => $data['courier_name'],
                'branch_id' => $branchId,
            ];

            # Decide condition
            $where = [];

            if (!empty($data['update_status']) && !empty($data['courier_id'])) {
                $where = ['id' => $data['courier_id']];
                $message = 'updated successfully!';
            } else {
                $where = ['name' => $data['courier_name'], 'branch_id' => $branchId];
                $message = 'created successfully!';
            }

            # Execute updateOrCreate
            $status = Courier::updateOrCreate($where, $addUpdate);

            # Return error if failed
            if (!$status) {
                return Utility::apiError('Fail to create courier', [], 221);
            }

            # Return success
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error('Courier creation error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCourier(Request $request)
    {
        try {
            # Get requested fields
            $data = $request->only([
                'page',
                'per_page',
                'branch_id',
                'courier_name'
            ]);

            # Validate fields
            $validator = Validator::make($data, [
                'branch_id' => 'nullable|integer|exists:branches,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            # Branch info
            $branchId = Auth::user()['branch_id'] ?? $data['branch_id'] ?? null;

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Get courier data
            $query = Courier::whereNull('deleted_at');

            if (!empty($branchId)) {
                $query->where('branch_id', $branchId);
            }

            if (!empty($data['courier_name'])) {
                $query->where('name', 'like', '%' . $data['courier_name'] . '%');
            }

            # Get data
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $courierData = $query->orderBy('id', 'desc')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Courier data fetched', $courierData);
        } catch (Exception $ex) {
            Log::error('Courier fetch error: ' . $ex->getMessage());
            return Utility::apiError('Failed to fetch couriers.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteCourier(Request $request)
    {
        try {
            # Get requested fields
            $data = $request->only(['id']);

            # Validate fields
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:couriers,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Delete courier
            $records = Courier::where('id', $data['id'])->delete();
            if (!$records) {
                return Utility::apiError('Fail to delete Courier !', [], 221);
            }

            # Return response
            return Utility::apiSuccess('deleted successfully!', [], 200);
        } catch (Exception $ex) {
            Log::debug('Courier delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting courier.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
