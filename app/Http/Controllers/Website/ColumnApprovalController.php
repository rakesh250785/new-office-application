<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\ColumnApprovalModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Log;

class ColumnApprovalController extends Controller
{
    /**
     * Add or update a column request
     */
    public function addUpdateColumnApproval(Request $request)
    {
        try {
            // Extract input
            $data = $request->only([
                'sample',
                'api_type',
                'pharmacopoeia',
                'sales_person',
                'date',
                'matrices',

                'column_sample_analysis',
                'column_column',
                'column_hplc',
                'column_gc',

                'organisation',
                'location',
                'department',
                'contact_name',
                'designation',
                'email_fax',
                'mobile',

                'in_use_column_description',
                'required_column_description',
                'is_guard_column_used',
                'guard_column_details',
                'part_no',
                'is_brand_change_acceptable',

                'diluents_solvent',
                'standard_preparation',
                'mobile_phase',
                'flow_rate',
                'gradient_temp_program',
                'injection_volume',
                'detector',
                'detector_settings',
                'instrument_used',
                'additional_information',
                'expected_column_consumption',
                'problem_description',
                'analytical_method_monograph',
                'id',
            ]);

            // Validation rules (required fields marked with *)
            $validator = Validator::make($data, [
                'sample' => 'required|string|max:128',
                'api_type' => 'required|string|max:128',
                'pharmacopoeia' => 'required|string|max:128',
                'sales_person' => 'required|string|max:128',
                'date' => 'required|date',
                'matrices' => 'required|string',

                'column_sample_analysis' => 'sometimes|boolean',
                'column_column' => 'sometimes|boolean',
                'column_hplc' => 'sometimes|boolean',
                'column_gc' => 'sometimes|boolean',

                'organisation' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'contact_name' => 'required|string|max:255',
                'designation' => 'required|string|max:255',
                'email_fax' => 'required|string|max:255',
                'mobile' => 'required|string|max:50',

                'in_use_column_description' => 'required|string',
                'required_column_description' => 'required|string',

                'is_guard_column_used' => 'sometimes|string',
                'guard_column_details' => 'required|string',
                'part_no' => 'required',
                'is_brand_change_acceptable' => 'sometimes|boolean',
                'problem_description' => 'sometimes|nullable',

                'diluents_solvent' => 'sometimes|string|nullable',
                'standard_preparation' => 'sometimes|string|nullable',
                'mobile_phase' => 'sometimes|string|nullable',
                'flow_rate' => 'sometimes|string|nullable',
                'gradient_temp_program' => 'sometimes|string|nullable',
                'injection_volume' => 'sometimes|string|nullable',
                'detector' => 'sometimes|string|nullable',
                'detector_settings' => 'sometimes|string|nullable',
                'instrument_used' => 'sometimes|string|nullable',
                'additional_information' => 'sometimes|string|nullable',
                'expected_column_consumption' => 'sometimes|nullable|integer|min:0',

                'analytical_method_monograph' => 'sometimes|string|nullable',
                'id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Prepare payload
            $payload = [
                'sample' => $data['sample'] ?? null,
                'pharmacopoeia' => $data['pharmacopoeia'] ?? null,
                'sales_person' => $data['sales_person'] ?? null,
                'request_date' => isset($data['date']) ? Carbon::parse($data['date'])->format('Y-m-d') : null,
                'application_type' => $data['api_type'] ?? null,
                'matrices' => $data['matrices'] ?? null,

                'column_sample_analysis' => $data['column_sample_analysis'] ?? null,
                'column_column' => $data['column_column'] ?? null,
                'column_hplc' => $data['column_hplc'] ?? null,
                'column_gc' => $data['column_gc'] ?? null,

                'organisation' => $data['organisation'] ?? null,
                'location' => $data['location'] ?? null,
                'department' => $data['department'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'designation' => $data['designation'] ?? null,
                'email_or_fax' => $data['email_fax'] ?? null,
                'mobile' => $data['mobile'] ?? null,

                'in_use_column_description' => $data['in_use_column_description'] ?? null,
                'required_column_description' => $data['required_column_description'] ?? null,
                'is_guard_column_used' => $data['is_guard_column_used'] ?? null,
                'guard_column_details' => $data['guard_column_details'] ?? null,
                'part_no' => $data['part_no'] ?? null,
                'is_brand_change_acceptable' => filter_var($data['is_brand_change_acceptable'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'problem_description' => $data['problem_description'] ?? null,

                'diluents_solvent' => $data['diluents_solvent'] ?? null,
                'standard_preparation' => $data['standard_preparation'] ?? null,
                'mobile_phase' => $data['mobile_phase'] ?? null,
                'flow_rate_per_min' => $data['flow_rate'] ?? null,
                'gradient_temp_program' => $data['gradient_temp_program'] ?? null,
                'injection_volume' => $data['injection_volume'] ?? null,
                'detector' => $data['detector'] ?? null,
                'detector_settings' => $data['detector_settings'] ?? null,
                'instrument_used' => $data['instrument_used'] ?? null,
                'additional_information' => $data['additional_information'] ?? null,
                'expected_column_consumption' => isset($data['expected_column_consumption']) ? (int) $data['expected_column_consumption'] : null,

                'analytical_method_monograph' => $data['analytical_method_monograph'] ?? null,
                'sample_analysis_chromatogram' => $data['sample_analysis_chromatogram'] ?? false,

                'user_id' => Auth::id() ?? null,
            ];

            // Create or update record
            $record = ColumnApprovalModel::updateOrCreate(
                ['id' => $data['id'] ?? null],
                $payload
            );

            if (! $record) {
                return Utility::apiError('Failed to save column request', [], 221);
            }

            // Message
            $message = isset($data['id']) ? 'Updated successfully' : 'Created successfully';

            // Return response
            return Utility::apiSuccess($message, $record, 200);
        } catch (Exception $ex) {
            Log::error('Column Request add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    /**
     * Fetch one or many column requests with filters, search, date range and pagination.
     */
    public function getColumnApproval(Request $request)
    {
        try {
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'search',
                'id',
                'sales_person',
            ]);

            // Single record by id
            if (! empty($data['id'])) {
                $record = ColumnApprovalModel::find($data['id']);
                if (! $record) {
                    return Utility::apiError('Record not found', [], 404);
                }

                return Utility::apiSuccess('Record fetched successfully', $record, 200);
            }

            // Base query
            $query = ColumnApprovalModel::query();

            // Search across several fields
            if (! empty($data['search'])) {
                $search = $data['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('pharmacopoeia', 'like', "%{$search}%")
                        ->orWhere('sales_person', 'like', "%{$search}%")
                        ->orWhere('date', 'like', "%{$search}%")
                        ->orWhere('application_type', 'like', "%{$search}%")

                        ->orWhere('matrices', 'like', "%{$search}%")

                        ->orWhere('organisation', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('email_fax', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")

                        ->orWhere('in_use_column_desc', 'like', "%{$search}%")
                        ->orWhere('required_column_desc', 'like', "%{$search}%")
                        ->orWhere('is_guard_column_used', 'like', "%{$search}%")
                        ->orWhere('guard_column_details', 'like', "%{$search}%")
                        ->orWhere('part_no', 'like', "%{$search}%")

                        ->orWhere('brand_change_acceptable', 'like', "%{$search}%")
                        ->orWhere('diluents_solvent', 'like', "%{$search}%")
                        ->orWhere('standard_preparation', 'like', "%{$search}%")
                        ->orWhere('mobile_phase', 'like', "%{$search}%")
                        ->orWhere('flow_rate', 'like', "%{$search}%")
                        ->orWhere('gradient_temp_program', 'like', "%{$search}%")
                        ->orWhere('injection_volume', 'like', "%{$search}%")
                        ->orWhere('detector', 'like', "%{$search}%")
                        ->orWhere('detector_settings', 'like', "%{$search}%")
                        ->orWhere('instrument_used', 'like', "%{$search}%")
                        ->orWhere('additional_info', 'like', "%{$search}%")
                        ->orWhere('expected_column_consumption', 'like', "%{$search}%")

                        ->orWhere('sample_analysis_chromatogram', 'like', "%{$search}%")
                        ->orWhere('analytical_method', 'like', "%{$search}%");
                });
            }

            // Filter by sales_person
            if (! empty($data['sales_person'])) {
                $query->where('sales_person', $data['sales_person']);
            }

            // Date range filter on request_date or created_at (use request_date if available)
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('request_date', [
                    Carbon::parse($data['start_date'])->startOfDay()->toDateString(),
                    Carbon::parse($data['end_date'])->endOfDay()->toDateString(),
                ]);
            }

            // Pagination
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $list = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('Record list fetched successfully', $list, 200);
        } catch (Exception $ex) {
            Log::error('Column Request fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch records', ['exception' => $ex->getMessage()]);
        }
    }

    /**
     * Delete a column request (soft delete assumed)
     */
    public function deleteColumnApproval(Request $request)
    {
        try {
            $data = $request->only(['id']);

            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:column_approval,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $deleted = ColumnApprovalModel::where('id', $data['id'])->delete();

            if (! $deleted) {
                return Utility::apiError('Failed to delete record', [], 221);
            }

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Column Request delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting record.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
