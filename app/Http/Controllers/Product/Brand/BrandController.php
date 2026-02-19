<?php

namespace App\Http\Controllers\Product\Brand;

use App\Exports\BrandExport;
use App\Exports\Export;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function addUpdateBrand(Request $request)
    {
        try {
            // Extract and validate input
            $data = $request->only([
                'name',
                'branch_id',
                'brand_id',
                'update_status',
            ]);

            // Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'brand_id' => 'nullable|integer|exists:brands,id',

            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Map validated data
            $payload = [
                'name' => $data['name'],
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::id(),
            ];

            // Create or update record
            $brand = Brand::updateOrCreate(
                ['id' => $data['brand_id'] ?? null],
                $payload
            );

            // Return if fail
            if (! $brand) {
                return Utility::apiError('Failed to save brand', [], 221);
            }

            // Prepare message
            $message = isset($data['brand_id']) ? 'Updated successfully' : 'Created successfully';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    public function getBrand(Request $request)
    {
        try {
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'search',
            ]);

            // Export logic (async)
            if (! empty($data['download'])) {
                $columns = [
                    'name' => 'Brand Name',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'brand_'.now()->format('Ymd_His').'.xlsx';

                (new BrandExport($data, $columns, Brand::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Normal listing
            $query = Brand::with('branch:id,name')->whereNull('deleted_at');

            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%$search%"));
                });
            }

            if (! empty($data['branch_list'])) {
                $query->where('branch_id', $data['branch_list']);
            }

            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $brands = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('Brand list fetched successfully', $brands, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to fetch brand list', [
                'exception' => $ex->getMessage(),
            ]);
        }
    }

    public function deleteBrand(Request $request)
    {
        try {

            // Request id
            $data = $request->only(['id']);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => [
                    'required',
                    'integer',
                    'exists:brands,id',
                    function ($attribute, $value, $fail) {
                        $exists = Product::where('brand_id', $value)->exists();

                        if ($exists) {
                            $fail('This Brand is already assigned to a product.');
                        }
                    },
                ],
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Soft delete record
            $deleted = Brand::where('id', $data['id'])->delete();

            // Retunr if fail
            if (! $deleted) {
                return Utility::apiError('Failed to delete brand', [], 221);
            }

            // Return response
            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Brand delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting brand.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
