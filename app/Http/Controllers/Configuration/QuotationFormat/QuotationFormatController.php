<?php

namespace App\Http\Controllers\Configuration\QuotationFormat;

use App\Exports\QuotationFormatExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\QuotationFormat;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuotationFormatController extends Controller
{
    public function __construct() {}

    public function addUpdateQuotationFormat(Request $request)
    {
        try {
            // Extract fields
            $data = $request->only([
                'billing_address',
                'branch_address',
                'branch_id',
                'notes',
                'mobile',
                'email',
                'quotation_format_id',
                'update_status',
            ]);

            // Validation rules
            $validator = Validator::make($data, [
                'branch_id' => 'required',
                'billing_address' => 'required',
                'branch_address' => 'required',
                'notes' => 'required',
                'mobile' => 'required',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('quotation_formats', 'email')
                        ->ignore($data['quotation_format_id'] ?? null),
                ],
                'quotation_format_id' => 'nullable|integer|exists:quotation_formats,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Data mapping
            $arr = [
                'billing_address' => $data['billing_address'],
                'branch_address' => $data['branch_address'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'notes' => $data['notes'],
                'user_id' => Auth::id(),
                'branch_id' => $data['branch_id'],
            ];

            // Update or create
            $format = QuotationFormat::updateOrCreate(
                ['id' => $data['quotation_format_id'] ?? null],
                $arr
            );

            // Return if fail
            if (! $format) {
                return Utility::apiError('Failed to save quotation format', [], 221);
            }

            // Message define
            $message = $data['quotation_format_id']
                ? ' updated successfully'
                : ' created successfully';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in quotation format', ['exception' => $ex->getMessage()]);
        }
    }

    public function getQuotationFormat(Request $request)
    {
        try {
            // Extract request fields
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'search',
            ]);

            // Export file
            if (! empty($data['download'])) {
                $columns = [
                    'email' => 'Email',
                    'mobile' => 'Mobile',
                    'billing_address' => 'Billing Address',
                    'branch_address' => 'Branch Address',
                    'notes' => 'Notes',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'quotation_format_'.now()->format('Ymd_His').'.xlsx';

                (new QuotationFormatExport($data, $columns, QuotationFormat::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Base query with relationships
            $query = QuotationFormat::with('branch:id,name')
                ->whereNull('deleted_at');

            // Global free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%$search%")
                        ->orWhere('mobile', 'like', "%$search%")
                        ->orWhere('billing_address', 'like', "%$search%")
                        ->orWhere('branch_address', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%")
                        ->orWhereHas('branch', function ($b) use ($search) {
                            $b->where('name', 'like', "%$search%");
                        });
                });
            }

            // Branch filter
            if (! empty($data['branch_list'])) {
                $query->where('branch_id', $data['branch_list']);
            }

            // Date range filter
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            if (Utility::checkViewPermission('quotation_format')) {
                $query->where('user_id', Auth::id());
            }

            // Normal paginated response
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $quotationFormatData = $query->orderByDesc('id')->paginate($perPage);

            // Return response
            return Utility::apiSuccess('Quotation Format list fetched successfully', $quotationFormatData, 200);
        } catch (Exception $ex) {
            Log::error('Quotation Format fetch error: '.$ex->getMessage(), ['trace' => $ex->getTraceAsString()]);

            return Utility::apiError('Something went wrong in quotation format', [
                'exception' => $ex->getMessage(),
            ], 500);
        }
    }

    public function deleteQuotationFormat(Request $request)
    {
        try {
            // Get requested fields
            $data = $request->only(['id']);

            // Validate fields
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:quotation_formats,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Delete courier
            $records = QuotationFormat::where('id', $data['id'])->delete();
            if (! $records) {
                return Utility::apiError('Fail to delete quotaion format !', [], 221);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully!', [], 200);
        } catch (Exception $ex) {
            Log::debug('Quiotation delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting quotation format.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
