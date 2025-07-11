<?php

namespace App\Http\Controllers\Product\Brand;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Brand;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class BrandController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateBrand(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['id', 'name']);

            # Add update condition
            $isUpdate = $data['id'] ?? null;

            # Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'id' => 'nullable|sometimes',
            ]);

            # Validation rule
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # data array
            $data = [
                'name' => $data['name'] ?? null,
            ];

            # Add update records
            if ($isUpdate) {
                $data['updated_at'] = Carbon::now();
            } else {
                $data['branch_id'] = Auth::user()->branch_id;
                $data['created_at'] = Carbon::now();
                $data['deleted_at'] = null;
            }

            # Add update branch
            $brand = Brand::updateOrCreate(
                ['id' => $data['id']],
                $data
            );

            # Return response
            return Utility::apiSuccess($isUpdate ? 'Brand updated successfully.' : 'Brand created successfully.', $brand, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving addUpdateBrand', ['exception' => $ex->getMessage()]);
        }
    }
    public function getBrands(Request $request)
    {
        try {
            # Get the specific fields
            $data = $request->only(['page', 'per_page', 'search']);

            # Pagination daata
            $perPage = $data['per_page'] ?? 10;

            # Get brand data
            $brands = Brand::whereNull('deleted_at')->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Brand list fetched', $brands, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching getBrands', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteBrand(Request $request)
    {
        try {

            # Get specific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|numeric|exists:brands,id'
            ]);

            # Return if fail
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete brand
            $deleted = Brand::where('id', $data['id'])->delete();

            if (!$deleted) {
                return Utility::apiError('Error while deleting brand', [], 221);
            }

            return Utility::apiSuccess('Brand deleted successfully.', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting brand', ['exception' => $ex->getMessage()]);
        }
    }
}
