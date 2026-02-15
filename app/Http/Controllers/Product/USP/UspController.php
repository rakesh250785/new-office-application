<?php

namespace App\Http\Controllers\Product\USP;

use App\Exports\UspExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Usp;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UspController extends Controller
{
    public function __construct() {}

    public function addUpdateUSP(Request $request)
    {
        try {
            // Extract fields
            $data = $request->only([
                'usp_type',
                'packing_details',
                'usp_brand',
                'category_id',
                'principal_id',
                'usp_id',
            ]);

            // Validation rules
            $validator = Validator::make($data, [
                'usp_type' => [
                    'required',
                    Rule::unique('usps', 'usp_type')->ignore($data['usp_id'] ?? null, 'id'),
                ],
                'packing_details' => 'required',
                'usp_brand' => 'required',
                'category_id' => 'required|integer|exists:category_types,id',
                'principal_id' => 'required|integer|exists:principals,id',
                'usp_id' => 'nullable|integer|exists:usps,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Data mapping
            $arr = [
                'usp_type' => $data['usp_type'],
                'packing_details' => $data['packing_details'],
                'usp_brand' => $data['usp_brand'],
                'category_id' => $data['category_id'],
                'principal_id' => $data['principal_id'],
                'user_id' => Auth::id(),
                'branch_id' => Auth::user()['branch_id'],
            ];

            // Update or create
            $format = Usp::updateOrCreate(
                ['id' => $data['usp_id'] ?? null],
                $arr
            );

            // Return if fail
            if (! $format) {
                return Utility::apiError('Failed to save usp', [], 221);
            }

            // Message define
            $message = $data['usp_id']
                ? ' updated successfully'
                : ' created successfully';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in usp format', ['exception' => $ex->getMessage()]);
        }
    }

    public function getUSP(Request $request)
    {
        try {
            // Get specific fields
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'category_list',
                'usp_list',
                'brand_list',
                'principal_list',
                'search',
            ]);

            // Async Export
            if (! empty($data['download'])) {
                $columns = [
                    'usp_type' => 'USP Type',
                    'packing_details' => 'Packing Details',
                    'usp_brand' => 'USP Brand',
                    'principal.type' => 'Principal',
                    'category.name' => 'Category',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'usp_'.now()->format('Ymd_His').'.xlsx';

                // Queue async export to avoid timeout/PDO issues
                (new UspExport($data, $columns, Usp::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Base query with relationships
            $query = Usp::with([
                'branch:id,name',
                'principal:id,type',
                'category:id,name',
            ])->whereNull('deleted_at');

            // Global free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('usp_type', 'like', "%$search%")
                        ->orWhere('packing_details', 'like', "%$search%")
                        ->orWhere('usp_brand', 'like', "%$search%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%$search%"))
                        ->orWhereHas('principal', fn ($b) => $b->where('type', 'like', "%$search%"))
                        ->orWhereHas('category', fn ($b) => $b->where('name', 'like', "%$search%"));
                });
            }

            // Category filter
            if (! empty($data['category_list'])) {
                $query->whereIn('category_id', (array) $data['category_list']);
            }

            // Usp Filter
            if (! empty($data['usp_list'])) {
                $query->whereIn('id', (array) $data['usp_list']);
            }

            // Branch filter
            if (! empty($data['brand_list'])) {
                $query->whereIn('id', (array) $data['brand_list']);
            }

            // Principal filter
            if (! empty($data['principal_list'])) {
                $query->whereIn('principal_id', (array) $data['principal_list']);
            }

            // Date filter
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            // Paginate results
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $uspData = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('USP list fetched successfully', $uspData, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in USP', [
                'exception' => $ex->getMessage(),
            ]);
        }
    }

    public function deleteUSP(Request $request)
    {
        try {
            // Get requested fields
            $data = $request->only(['id']);

            // Validate fields
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:usps,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Delete courier
            $records = Usp::where('id', $data['id'])->delete();
            if (! $records) {
                return Utility::apiError('Fail to delete usp !', [], 221);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully!', [], 200);
        } catch (Exception $ex) {
            Log::debug('Quiotation delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting usp.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
