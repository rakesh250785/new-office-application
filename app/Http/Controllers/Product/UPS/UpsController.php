<?php

namespace App\Http\Controllers\Product\UPS;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Usp;
use Exception, Log;
use App\Helpers\Utility;

class UpsController extends Controller
{
    public function addUpdateUsp(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['usp_type', 'paking', 'brand_id', 'category_type_id', 'principal_id', 'id']);

            # Validation rule
            $validator = Validator::make($data, [
                'usp_type' => 'required|string',
                'packing' => 'required|string',
                'brand_id' => 'required',
                'category_type_id' => 'required|string',
                'principal_id' => 'required|string',
                'id' => 'nullable|sometimes|numeric',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Prepare arr
            $payload = [
                'usp_type' => $data['usp_type'] ?? null,
                'packing' => $data['packing'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'category_type_id' => $data['category_type_id'] ?? null,
                'principal_id' => $data['principal_id'] ?? null,
                'branch_id' => Auth::user()->branch_id,
            ];

            # Condition
            $condition = ['id' => $request->id];

            # Add / update
            $usp = Usp::updateOrCreate($condition, $payload);

            # Return if fail
            if (!$usp) {
                return Utility::apiError('Failed to save USP', [], code: 221);
            }

            # message define
            $message = $data['id'] ? 'USP updated successfully.' : 'USP created successfully.';

            # Retun response
            return Utility::apiSuccess($message, $usp, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving USP', ['exception' => $ex->getMessage()]);
        }
    }

    public function getUspList(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['search', 'category_id', 'usp_type_id', 'brand_id', 'per_page']);

            # Get usp list
            $query = Usp::whereNull('deleted_at')->orderByDesc('id');

            # Filter condition
            if (!empty($data['category_id'])) {
                $query->where('category_id', $data['category_id']);
            }
            if (!empty($data['usp_type_id'])) {
                $query->where('usp_type_id', $data['usp_type_id']);
            }
            if (!empty($data['brand_id'])) {
                $query->where('brand_id', $data['brand_id']);
            }
            if (!empty($data['principal_id'])) {
                $query->where('principal_id', $data['principal_id']);
            }

            # Get usp records
            $usp = $query->paginate($data['per_page'] ?? 10);

            # Return response
            return Utility::apiSuccess('USP list fetched.', $usp, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching USP list', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteUsp(Request $request)
    {
        try {

            # Get specific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete id
            $record = Usp::where('id', $data['id'])->delete();

            # Return if error
            if (!$record) {
                return Utility::apiError('USP record not found.', [], 221);
            }

            # Retunr response
            return Utility::apiSuccess('USP deleted successfully.', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting USP', ['exception' => $ex->getMessage()]);
        }
    }
}
