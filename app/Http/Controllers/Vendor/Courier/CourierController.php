<?php

namespace App\Http\Controllers\Vendor\Courier;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Courier;
use App\Models\Branch;
use Exception;

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

            # Validate fields
            $validator = Validator::make($data, [
                'courier_name' => 'required|string|max:255',
                'branch_id' => 'nullable|integer|exists:branches,id',
                'courier_id' => 'nullable|integer|exists:couriers,id',
                'update_status' => 'nullable|sometimes|boolean'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Get branch id
            $branchId = $data['branch_id'] ?? auth()->user()->branch_id;

            # Get branch name
            $branch = Branch::find($branchId);
            $branchName = $branch->name ?? null;

            # Return if branch not found
            if (!$branchName) {
                return Utility::apiSuccess('Branch not found', [], 404);
            }

            # Prepare arr
            $addUpdate = [];
            $status = false;
            $message = 'Courier created successfully!';

            # Create courier if update_status is not set
            if (empty($data['update_status'])) {
                $addUpdate['courier_name'] = $data['courier_name'];
                $addUpdate['branch_id'] = $branchId;
                $status = Courier::create($addUpdate);
            }

            # Update courier if branch_id and update_status exist
            if (!empty($data['branch_id']) && !empty($data['update_status'])) {
                $addUpdate['courier_name'] = $data['courier_name'];
                $status = Courier::where('id', $data['courier_id'])->update($addUpdate);
                $message = 'Courier updated successfully!';
            }

            # Return if fail
            if (!$status) {
                return Utility::apiError('Fail to create courier');
            }

            # Return response
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
                'branch_id'
            ]);

            # Validate fields
            $validator = Validator::make($data, [
                'branch_id' => 'nullable|integer|exists:branches,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Get courier data
            $query = Courier::whereNull('deleted_at');

            if (!empty($data['branch_id'])) {
                $query->where('branch_id', $data['branch_id']);
            }

            # Get data
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $courierData = $query->orderBy('in_courier_id', 'desc')
                ->paginate($perPage);

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
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete courier
            $records = Courier::where('id', $data['id'])->delete();
            if (!$records) {
                return Utility::apiError('Fail to delete Courier !');
            }

            # Return response
            return Utility::apiSuccess('Courier deleted successfully!', [], 200);
        } catch (Exception $ex) {
            Log::debug('Courier delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting courier.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
