<?php

namespace App\Http\Controllers\Cofiguration\QuotationFormat;

use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\QuotationFormat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exports\Export;
use App\Helpers\Utility;
use Carbon\Carbon;
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
                'email' => [
                    'required',
                    'email',
                    Rule::unique('quatation_formats', 'email')
                        ->ignore($data['quotation_format_id'] ?? null)
                ],
                'quotation_format_id' => 'nullable|integer|exists:quotation_formats,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Data mapping
            $arr = [
                'billing_address' => $data['billing_address'],
                'branch_address' => $data['branch_address'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'notes' => $data['notes'],
                'user_id' => Auth::id(),
                'branch_id' => $data['branch_id'],
            ];

            # Update or create
            $format = QuotationFormat::updateOrCreate(
                ['id' => $data['quotation_format_id'] ?? null],
                $arr
            );

            # Return if fail
            if (!$format) {
                return Utility::apiError('Failed to save quotation format', [], 221);
            }

            # Message define
            $message = $data['quotation_format_id']
                ? ' updated successfully'
                : ' created successfully';

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in quotation format', ['exception' => $ex->getMessage()]);
        }
    }

    public function getQuotationFormat(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'search',
            ]);

            # Load query with branch relationship
            $query = QuotationFormat::with([
                'branch' => function ($q) {
                    $q->select('id', 'name');
                }
            ])->whereNull('deleted_at');

            # Global free-text search
            if (!empty($data['search'])) {
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

            # Branch filter
            if (!empty($data['branch_list'])) {
                $query->whereIn('branch_id', $data['branch_list']);
            }

            # Date filter
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay()
                ]);
            }

            # Export logic
            if (!empty($data['download'])) {
                $columns = [
                    'email' => 'Email',
                    'mobile' => 'Mobile',
                    'billing_address' => 'Billing Address',
                    'branch_address' => 'Branch Address',
                    'notes' => 'Notes',
                    'branch.name' => 'Branch Name',
                    'created_at' => 'Date',
                ];

                $filename = 'quotation_format_' . now()->format('Ymd_His') . '.xlsx';
                return Excel::download(new Export($query, $columns), $filename);
            }

            # Pagination
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $quotationFormatData = $query->orderByDesc('id')->paginate($perPage);

            # Retunr response
            return Utility::apiSuccess('List Quotation Format', $quotationFormatData, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in quotation format', [
                'exception' => $ex->getMessage()
            ]);
        }
    }

    public function deleteQuotationFormat(Request $request)
    {
        try {
            # Get requested fields
            $data = $request->only(['id']);

            # Validate fields
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:quotation_formats,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Delete courier
            $records = QuotationFormat::where('id', $data['id'])->delete();
            if (!$records) {
                return Utility::apiError('Fail to delete quotaion format !', [], 221);
            }

            # Return response
            return Utility::apiSuccess('deleted successfully!', [], 200);
        } catch (Exception $ex) {
            Log::debug('Quiotation delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting quotation format.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
