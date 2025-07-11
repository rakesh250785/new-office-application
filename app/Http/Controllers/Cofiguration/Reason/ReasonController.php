<?php

namespace App\Http\Controllers\Cofiguration\Reason;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Reason;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class ReasonController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateReason(Request $request)
    {
        try {
            # Extract specific fields
            $data = $request->only(['id', 'reason_name', 'reason_type_id']);

            # Validate rule
            $validator = Validator::make($data, [
                'reason_name' => 'required|string|max:255',
                'reason_type_id' => 'required|integer',
                'id' => 'nullable|integer|exists:reasons,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Determine if update
            $isUpdate = !empty($data['id']);

            # Prepare base payload
            $payload = [
                'reason_name' => $data['reason_name'],
                'reason_type_id' => $data['reason_type_id'],
                'updated_at' => Carbon::now(),
            ];

            # If id not exist
            if (!$isUpdate) {
                $payload['branch_id'] = Auth::user()->branch_id ?? 0;
                $payload['is_deleted'] = 0;
            }

            # Update or Create record
            $reason = Reason::updateOrCreate(
                ['id' => $data['id'] ?? null],
                $payload
            );

            # Response message
            $msg = $isUpdate ? 'Reason updated successfully' : 'Reason created successfully';
            return Utility::apiSuccess($msg, $reason, $isUpdate ? 200 : 221);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving reason', ['exception' => $ex->getMessage()]);
        }
    }

    public function getReasons(Request $request)
    {
        try {
            # Get specific fields
            $pagination = $request->only(['per_page', 'page', 'search']);

            # Get reason
            $reasons = Reason::whereNull('deleted_at')->orderByDesc('id')->paginate($pagination['per_page'] ?? 10);

            # Return response
            return Utility::apiSuccess('Reason list fetched successfully', $reasons);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching reasons', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteReason(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['id']);

            # Validate rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:reasons,int_id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete reason
            $deleted = Reason::where('id', $data['id'])->delete();

            # Return if fail to delete
            if (!$deleted) {
                return Utility::apiError('Failed to delete reason', [], 400);
            }

            # Return response
            return Utility::apiSuccess('Reason deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting reason', ['exception' => $ex->getMessage()]);
        }
    }
}
