<?php

namespace App\Http\Controllers\Product\Product;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception, Log;

class ProductController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Create or update product
     */
    public function addUpdateProduct(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only([
                'id',
                'part_no',
                'hsn_no',
                'principal_id',
                'category_id',
                'brand_id',
                'price',
                'igst_rate',
                'discount',
                'quantiy',
                'description',
                'additional_description',
                'specification'
            ]);

            # Validation rule
            $rules = [
                'part_no' => 'required|string|unique:product,part_no' . ($data['id'] ? ',' . $data['id'] . ',id' : ''),
                'hsn_no' => 'required|string',
                'principal_id' => 'required',
                'category_id' => 'required',
                'brand_id' => 'required',
                'price' => 'required|numeric',
                'uom' => 'required',
                'igst_rate' => 'required|string',
                'discount' => 'required|numeric',
                'quantiy' => 'required|numeric',
                'description' => 'required|string',
                'additional_description' => 'required|string',
                'specification' => 'required|string',
            ];

            # Apply validation rule
            $validator = Validator::make($data, $rules, [
                'part_no.unique' => 'Part name has already been taken.',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Format data
            $payload = [
                'part_no' => $data['part_no'] ?? null,
                'hsn_no' => $data['hsn_no'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'price' => $data['price'] ?? null,
                'igst_rate' => $data['igst_rate'] ?? null,
                'discount' => $data['discount'] ?? null,
                'quantiy' => $data['quantiy'] ?? null,
                'description' => $data['description'] ?? null,
                'additional_description' => $data['description'] ?? null,
                'principal_id' => $data['principal_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'branch_id' => Auth::user()->branch_id,
                'price_updated_at' => Carbon::now(),
                'quantity_updated_at' => Carbon::now(),
            ];

            # If product is null, add new product
            if (empty($data['id'])) {
                $payload['created_at'] = Carbon::now();
                $payload['price_updated_at'] = Carbon::now();
                $payload['quantity_updated_at'] = Carbon::now();
                $product = Product::create($payload);
                return Utility::apiSuccess('Product created successfully.', $product, 201);
            }

            # Update product
            $existing = Product::find($data['id']);

            # Return if not found
            if (!$existing) {
                return Utility::apiError('Product not found.', [], 221);
            }

            # Compare price
            if ($existing['price'] != ($data['price'] ?? null)) {
                $payload['price_update_at'] = Carbon::now();
            }

            # Compare quantiy
            if ($existing['quantiy'] != ($data['quantiy'] ?? null)) {
                $payload['quantity_update'] = Carbon::now();
            }

            # Update price
            $existing->update($payload);

            # Return response
            return Utility::apiSuccess('Product updated successfully.', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving product.', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * Get product list
     */
    public function listProduct(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['search', 'per_page']);

            # Get product
            $query = Product::whereNull('deleted_at')->orderByDesc('id');

            # Search fields is not empty
            if (!empty($data['search'])) {
                $query->where('part_no', 'like', "%{$data['search']}%");
            }

            # Pagination set
            $perPage = (int) $data['per_page'] ?? 15;

            # Get recods
            $products = $query->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Product list fetched successfully.', $products);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching products.', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:product,id',
            ]);

            # Return if fail
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete product
            $deleted = Product::where('id', $data['id'])->update(['deleted_at' => Carbon::now()]);

            # Return if fail to delete
            if (!$deleted) {
                return Utility::apiError('Failed to delete product.', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Product deleted successfully.');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting product.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
