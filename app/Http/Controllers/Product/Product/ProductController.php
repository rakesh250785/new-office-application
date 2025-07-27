<?php

namespace App\Http\Controllers\Product\Product;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception, Log;
use App\Exports\Export;

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
                'part_no',
                'hsn_no',
                'principal_id',
                'category_id',
                'brand_id',
                'uom',
                'price',
                'quantity',
                'igst_rate',
                'discount',
                'quantity',
                'description',
                'additional_description',
                'specification',
                'product_id',
            ]);

            # Validation rule
            $rules = [
                'part_no' => 'required|string|unique:products,part_no' . ($data['part_no'] ? ',' . $data['part_no'] . ',part_no' : ''),
                'hsn_no' => 'required|string',
                'principal_id' => 'required',
                'category_id' => 'required',
                'brand_id' => 'required',
                'price' => 'required|numeric',
                'uom' => 'required',
                'igst_rate' => 'required|string',
                'discount' => 'required|numeric',
                'quantity' => 'required|numeric',
                'description' => 'required|string',
                'additional_description' => 'required|string',
                'specification' => 'required',
                'product_id' => 'nullable|numeric|exists:products,id',
            ];

            # Apply validation rule
            $validator = Validator::make($data, $rules, [
                'part_no.unique' => 'Part name has already been taken.',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Format data
            $payload = [
                'part_no' => $data['part_no'] ?? null,
                'hsn_no' => $data['hsn_no'] ?? null,
                'uom' => $data['uom'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'price' => $data['price'] ?? null,
                'igst_rate' => $data['igst_rate'] ?? null,
                'discount' => $data['discount'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'description' => $data['description'] ?? null,
                'additional_description' => $data['description'] ?? null,
                'specification' => $data['specification'] ?? null,
                'principal_id' => $data['principal_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'branch_id' => Auth::user()['branch_id'],
                'price_updated_at' => Carbon::now(),
                'quantity_updated_at' => Carbon::now(),
                'user_id' => Auth::user()['id'],
            ];

            # If product is null, add new product
            if (empty($data['product_id'])) {
                $payload['created_at'] = Carbon::now();
                $payload['price_updated_at'] = Carbon::now();
                $payload['quantity_updated_at'] = Carbon::now();
                $product = Product::create($payload);
                if (!$product) {
                    return Utility::apiError('Fail to create product.', [], 221);
                }

                # Return response
                return Utility::apiSuccess('created successfully.', [], 200);
            }

            # Update product
            $existing = Product::find($data['product_id']);

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
            return Utility::apiSuccess('updated successfully.', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error saving product.', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * Get product list
     */
    public function getProduct(Request $request)
    {
        try {

            # Request specific fields
            $data = $request->only(['search', 'download', 'per_page', 'start_date', 'end_date', 'principal_list', 'brand_list', 'category_list']);

            # Get products
            $query = Product::with('principal:id,type', 'category:id,name', 'brand:id,name')
                ->whereNull('deleted_at')
                ->orderByDesc('id');

            # Apply search filter across multiple fields
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('part_no', 'like', "%{$search}%")
                        ->orWhere('hsn_no', 'like', "%{$search}%")
                        ->orWhere('price', 'like', "%{$search}%")
                        ->orWhere('uom', 'like', "%{$search}%")
                        ->orWhere('igst_rate', 'like', "%{$search}%")
                        ->orWhere('discount', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('additional_description', 'like', "%{$search}%")
                        ->orWhere('specification', 'like', "%{$search}%")
                        ->orWhere('category_id', 'like', "%{$search}%")
                        ->orWhere('quantity', 'like', "%{$search}%")
                        ->orWhere('price_updated_at', 'like', "%{$search}%")
                        ->orWhere('quantity_updated_at', 'like', "%{$search}%")
                        ->orWhereHas(
                            'principal',
                            fn($b) =>
                            $b->where('type', 'like', "%$search%")
                        )
                        ->orWhereHas(
                            'category',
                            fn($b) =>
                            $b->where('name', 'like', "%$search%")
                        )
                        ->orWhereHas(
                            'brand',
                            fn($b) =>
                            $b->where('name', 'like', "%$search%")
                        );
                });
            }

            # Apply individual filters
            if (!empty($data['principal_list'])) {
                $query->where('principal_id', $data['principal_list']);
            }
            if (!empty($data['category_list'])) {
                $query->where('category_id', $data['category_list']);
            }
            if (!empty($data['brand_list'])) {
                $query->where('brand_id', $data['brand_list']);
            }
            if (!empty($data['branch_list'])) {
                $query->where('branch_id', $data['branch_list']);
            }

            # Date filter
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay()
                ]);
            }

            if (!empty($data['download'])) {
                $columns = [
                    'part_no' => 'Part No.',
                    'hsn_no' => 'HSN No.',
                    'price' => 'Price',
                    'uom' => 'UOM',
                    'igst_rate' => 'IGST Rate',
                    'discount' => 'Discount',
                    'description' => 'Description',
                    'additional_description' => 'Additional Description',
                    'category.name' => 'Category',
                    'brand.name' => 'Brand',
                    'principal.name' => 'Principal',
                    'quantity' => 'Quantity',
                    'price_updated_at' => 'Price Updated Date',
                    'quantity_updated_at' => 'Quantity Updated Date',
                    'branch.name' => 'Branch Name',
                    'created_at' => 'Date',
                ];
                $filename = 'product' . now()->format('Ymd_His') . '.xlsx';
                return Excel::download(new Export($query, $columns), $filename);
            }

            $perPage = (int) ($data['per_page'] ?? 15);
            $products = $query->paginate($perPage);

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
                'id' => 'required|integer|exists:products,id',
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
            return Utility::apiSuccess('deleted successfully.', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting product.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
