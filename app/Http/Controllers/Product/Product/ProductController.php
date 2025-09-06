<?php

namespace App\Http\Controllers\Product\Product;
use App\Exports\ProductExport;
use App\Imports\ProductUploadImport;
use App\Models\ImportJob;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception, Log;
use App\Imports\HeaderCheckImport;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

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
                'description',
                'additional_description',
                'specification',
                'product_id',
            ]);

            # validation rules
            $rules = [
                'part_no' => [
                    'required',
                    'string',
                    Rule::unique('products', 'part_no')->ignore($data['product_id'] ?? null),
                ],
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

            # image rule (file)
            $fileRules = [
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            ];

            # validate using full request so file keys are included
            $validator = Validator::make($request->all(), array_merge($rules, $fileRules), [
                'part_no.unique' => 'Part name has already been taken.',
                'image.image' => 'Uploaded file must be an image.',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # base payload (do NOT set price_updated_at/quantity_updated_at here for update)
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
                'additional_description' => $data['additional_description'] ?? null,
                'specification' => $data['specification'] ?? null,
                'principal_id' => $data['principal_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'branch_id' => Auth::user()['branch_id'] ?? null,
                'user_id' => Auth::user()['id'] ?? null,
            ];

            # handle uploaded image (if present)
            $newImagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('products', 'public');
                $imageUrl = Storage::url($path);
                $imageUrl = url(Storage::url($path));
                $payload['image'] = $imageUrl;
                $newImagePath = $path;
            }

            # CREATE
            if (empty($data['product_id'])) {
                $payload['created_at'] = Carbon::now();
                $payload['price_updated_at'] = Carbon::now();
                $payload['quantity_updated_at'] = Carbon::now();

                $product = Product::create($payload);
                if (!$product) {
                    return Utility::apiError('Fail to create product.', [], 221);
                }

                # return created product (optional: include image url)
                return Utility::apiSuccess('created successfully.', ['product' => $product], 200);
            }

            # UPDATE
            $existing = Product::find($data['product_id']);
            if (!$existing) {
                return Utility::apiError('Product not found.', [], 221);
            }

            # If new image uploaded, delete previous file from disk (best-effort)
            if (!empty($newImagePath) && !empty($existing->image)) {
                try {
                    $existingImage = $existing->image;
                    $storagePrefix = '/storage/';
                    if (strpos($existingImage, $storagePrefix) !== false) {
                        $relative = substr($existingImage, strpos($existingImage, $storagePrefix) + strlen($storagePrefix)); // products/abc.jpg
                        if ($relative && Storage::disk('public')->exists($relative)) {
                            Storage::disk('public')->delete($relative);
                        }
                    } else {
                        # possibly stored as 'products/abc.jpg'
                        if (Storage::disk('public')->exists($existingImage)) {
                            Storage::disk('public')->delete($existingImage);
                        }
                    }
                } catch (Exception $ex) {
                    Log::warning('Failed to delete old product image: ' . $ex->getMessage());
                }
            }

            # timestamp updates only when value actually changes
            if (isset($data['price']) && $existing->price != $data['price']) {
                $payload['price_updated_at'] = Carbon::now();
            }

            if (isset($data['quantity']) && $existing->quantity != $data['quantity']) {
                $payload['quantity_updated_at'] = Carbon::now();
            }

            # perform update
            $existing->update($payload);

            // return updated product (optional: include image url)
            return Utility::apiSuccess('updated successfully.', ['product' => $existing->fresh()], 200);
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
                    'principal.type' => 'Principal',
                    'quantity' => 'Quantity',
                    'price_updated_at' => 'Price Updated Date',
                    'quantity_updated_at' => 'Quantity Updated Date',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'product_' . now()->format('Ymd_His') . '.xlsx';

                (new ProductExport($data, $columns, Product::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }


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
                $query->whereIn('principal_id', $data['principal_list']);
            }
            if (!empty($data['category_list'])) {
                $query->whereIn('category_id', $data['category_list']);
            }
            if (!empty($data['brand_list'])) {
                $query->whereIn('brand_id', $data['brand_list']);
            }
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

    public function uploadProductFile(Request $request)
    {
        try {
            # Validation error
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:10240',
                'upload_type' => 'required|in:price,quantity',
            ]);

            # Check header
            $import = new HeaderCheckImport;
            Excel::import($import, $request->file('file'));

            $headers = array_map('strtolower', $import->headers);
            $expected = ['part_no', $request->upload_type];
            $missing = array_diff($expected, $headers);

            # Return if error
            if (!empty($missing)) {
                return Utility::apiError("Invalid file header. Missing: " . implode(", ", $missing), 221);
            }

            # Get total file row
            $totalRows = Excel::toCollection(new HeaderCheckImport, $request->file('file'))[0]->count();

            # Miantain import
            $job = ImportJob::create([
                'file_name' => $request->file('file')->getClientOriginalName(),
                'upload_type' => $request->upload_type,
                'status' => 'pending',
                'total_rows' => $totalRows,
                'processed_rows' => 0,
            ]);

            # Process for import
            Excel::import(
                new ProductUploadImport($request->upload_type, $job->id),
                $request->file('file')
            );
            # Return response
            return Utility::apiSuccess('File uploaded successfully. Processing started.', [
                'job_id' => $job->id,
            ]);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error uploadProductFile product.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function importStatus($id)
    {

        try {
            $job = ImportJob::find($id);

            if (!$job) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Job not found',
                ], 404);
            }

            return Utility::apiSuccess('File uploaded successfully. Processing started.', [
                'status' => $job->status,
                'processed_rows' => $job->processed_rows,
                'total_rows' => $job->total_rows,
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error importStatus product.', ['exception' => $ex->getMessage()], 500);
        }
    }

}
