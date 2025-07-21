<?php

namespace App\Http\Controllers\Cofiguration\QuotationFormat;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\QuatationFormat;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class QuotationFormatController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateQuotationFormat(Request $request)
    {
        try {
            # Extract fields
            $data = $request->only([
                'billing_address',
                'branch_address',
                'branch_id',
                'notes',
                'mobile',
                'email',
                'quotation_format_id',
                'update_status'
            ]);

            # Validation rules
            $validator = Validator::make($data, [
                'branch_id' => 'required',
                'billing_address' => 'required',
                'branch_address' => 'required',
                'notes' => 'required',
                'mobile' => 'required',
                'email' => 'required|email',
                'quotation_format_id' => 'nullable|sometimes|numeric|exists:quatation_formats,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Data mapping
            $arr = [
                'billing_address' => $data['billing_address'],
                'branch_address' => $data['branch_address'],
                'branch_email' => $data['email'],
                'branch_phnumber' => $data['mobile'],
                'stn_billing_note' => $data['notes'],
                'user_id' => Auth::id(),
                'branch_id' => $data['branch_id'],
                'city_name' => $data['city_name'] ?? null,
            ];

            # Update or create
            $format = QuatationFormat::updateOrCreate(
                ['id' => $data['quotation_format_id'] ?? null],
                $arr
            );

            # Return if fail
            if (!$format) {
                return Utility::apiError('Failed to save quotation format', [], 221);
            }

            # Message define
            $message = $data['quotation_format_id']
                ? 'format updated successfully'
                : 'format created successfully';

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in quotation format', ['exception' => $ex->getMessage()]);
        }
    }


    public function getQuatationFormat(Request $request)
    {
        try {
            # Get fields
            $data = $request->only([
                'search'
            ]);

            # Get branch info
            $query = QuatationFormat::whereNull('deleted_at')->orderByDesc('id');
            $branchInfo = Branch::get()->pluck('name', 'id');

            # Optional branch search    
            if (!empty($data['search'])) {
                if (isset($flp_branch[$request->search])) {
                    $query->where('int_branch_id', (int) $branchInfo[$data['search']]);
                }
            }

            # Get records
            $getREc = $query->paginate();

            # Response 
            return Utility::apiSuccess('List quotation format', $getREc, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong quotaion format', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteQuatationFormat(Request $request, $id)
    {
        try {

            # Get selected fields
            $data = $request->only([
                'id',
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete quotaion format
            $status = QuatationFormat::where('id', $id)->delete();

            # Return if fail
            if (!$status) {
                return Utility::apiError('Fail to deleted quotaion format', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Quotation format deleted successfully', [], 221);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error occurred while deleting quotaion format', ['exception' => $ex->getMessage()]);
        }
    }
}
