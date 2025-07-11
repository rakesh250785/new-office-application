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
use Carbon\Carbon;
use Exception;

class QuotationFormatController extends Controller
{
    public function __construct(){
    }

    public function addUpdateQuatationFormat(Request $request)
    {
        try {
            # Get selected fields
            $data = $request->only([
                'billing_address',
                'branch_address',
                'branch_id',
                'billing_notes',
                'mobile_no',
                'email_address',
                'city_name',
                'quotation_format_id',
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'branch_id' => 'required',
                'billing_address' => 'required',
                'branch_address' => 'required',
                'billing_notes' => 'required',
                'mobile_no' => 'required',
                'email_address' => 'required|email',
                'city_name' => 'required|string',
                'quotation_format_id' => 'nullable|sometimes'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Prepare arr
            $arr = [
                'billing_address' => $data['billing_address'] ?? null,
                'branch_address' => $data['branch_address'] ?? null,
                'branch_email' => $data['email_address'] ?? null,
                'branch_phnumber' => $data['mobile_no'] ?? null,
                'stn_billing_note' => $data['billing_notes'] ?? null,
                'user_id' => Auth::id(),
                'branch_id' => Auth::user()->branch_id,
                'city_name' => $data['city_name'] ?? null,
                'created_at' => Carbon::now(),
            ];

            # Default message
            $message = 'Quotaion format created successfully';

            # Update quotaion format
            if (!empty($data['quotation_format_id'])) {
                $status = QuatationFormat::where('id', $data['quotation_format_id'])->update($arr);
                if (!$status) {
                    return Utility::apiError('Fail to update quotation format', [], 221);
                }
                $message = 'Quotaion format updated successfully';
            }

            # Create quotation format
            $status = QuatationFormat::create($arr);

            # Retun if fail
            if (!$status) {
                return Utility::apiError('Fail to create courier', [], 221);
            }

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
