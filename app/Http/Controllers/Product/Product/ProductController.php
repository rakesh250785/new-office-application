<?php

namespace App\Http\Controllers\Product\Product;

use App\Exports\ProductExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\EnqueueProductImport;
use App\Models\Category;
use App\Models\ImportJob;
use App\Models\Parameter;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct() {}

    /**
     * Create or update product
     */
    public function addUpdateProduct(Request $request)
    {
        try {

            // Request specific fileds
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

            // Get column name
            $paramDefs = collect();
            if (! empty($data['category_id'])) {
                $cat = Category::find($data['category_id']);
                if ($cat && $cat->parameter_field) {
                    $ids = array_map('trim', explode(',', $cat->parameter_field));
                    $ids = array_values(array_filter($ids, 'is_numeric'));
                    if (! empty($ids)) {
                        $paramDefs = Parameter::select('id', 'parameter_name', 'column_name')
                            ->whereIn('id', $ids)
                            ->get();
                    }
                }
            }

            // Get in aray
            $allowedDynamicColumns = $paramDefs->pluck('column_name')->filter()->unique()->values()->all();

            // Validation rule
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
                'additional_description' => 'sometimes|nullable',
                'specification' => 'sometimes|nullable',
                'product_id' => 'nullable|numeric|exists:products,id',
            ];

            // File rule
            $fileRules = [
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            ];

            // If there is dynamic fields
            $dynamicRules = [];
            if (! empty($allowedDynamicColumns)) {
                foreach ($allowedDynamicColumns as $col) {
                    $dynamicRules[$col] = 'required';
                }
            }
            $fileRules = array_merge($rules, $fileRules, $dynamicRules);

            $data = $request->all();

            // Always replace "#" with space in all keys
            $normalized = [];
            foreach ($data as $key => $value) {
                $newKey = str_replace('#', ' ', $key);
                $normalized[$newKey] = $value;
            }
            // Validation rule apply
            $validator = Validator::make($normalized, $fileRules, [
                'part_no.unique' => 'Part name has already been taken.',
                'image.image' => 'Uploaded file must be an image.',
            ]);

            // Return validation rule
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Payload to add / update
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

            // Handle image
            $newImagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('products', 'public');
                $imageUrl = url(Storage::url($path));
                $payload['image'] = $imageUrl;
                $newImagePath = $path;
            }

            foreach ($allowedDynamicColumns as $col) {
                if (array_key_exists($col, $normalized)) {
                    $payload[$col] = $normalized[$col];
                }
            }

            // Add new product
            if (empty($data['product_id'])) {
                $payload['created_at'] = Carbon::now();
                $payload['price_updated_at'] = Carbon::now();
                $payload['quantity_updated_at'] = Carbon::now();
                $payload['updated_at'] = Carbon::now();

                $product = Product::insert($payload);
                if (! $product) {
                    return Utility::apiError('Fail to create product.', [], 221);
                }

                return Utility::apiSuccess('created successfully.', ['product' => $product], 200);
            }

            // Update product
            $existing = Product::find($data['product_id']);
            if (! $existing) {
                return Utility::apiError('Product not found.', [], 221);
            }

            if (! empty($newImagePath) && ! empty($existing->image)) {
                try {
                    $existingImage = $existing->image;
                    $storagePrefix = '/storage/';
                    if (strpos($existingImage, $storagePrefix) !== false) {
                        $relative = substr($existingImage, strpos($existingImage, $storagePrefix) + strlen($storagePrefix));
                        if ($relative && Storage::disk('public')->exists($relative)) {
                            Storage::disk('public')->delete($relative);
                        }
                    } else {
                        if (Storage::disk('public')->exists($existingImage)) {
                            Storage::disk('public')->delete($existingImage);
                        }
                    }
                } catch (Exception $ex) {
                    Log::warning('Failed to delete old product image: '.$ex->getMessage());
                }
            }

            if (isset($data['price']) && $existing->price != $data['price']) {
                $payload['price_updated_at'] = Carbon::now();
            }
            if (isset($data['quantity']) && $existing->quantity != $data['quantity']) {
                $payload['quantity_updated_at'] = Carbon::now();
            }

            // Update product
            $status = $existing->update($payload);
            if (! $status) {
                return Utility::apiError('Fail to update not product.', [], 221);
            }

            // Retunr response
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

            // Request specific fields
            $data = $request->only(['search', 'download', 'per_page', 'start_date', 'end_date', 'principal_list', 'brand_list', 'category_list']);

            if (! empty($data['download'])) {
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

                $filename = 'product_'.now()->format('Ymd_His').'.xlsx';

                (new ProductExport($data, $columns, Product::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Get products
            $query = Product::with('principal:id,type', 'category:id,name', 'brand:id,name')
                ->whereNull('deleted_at')
                ->orderByDesc('id');

            // Apply search filter across multiple fields
            if (! empty($data['search'])) {
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
                            fn ($b) => $b->where('type', 'like', "%$search%")
                        )
                        ->orWhereHas(
                            'category',
                            fn ($b) => $b->where('name', 'like', "%$search%")
                        )
                        ->orWhereHas(
                            'brand',
                            fn ($b) => $b->where('name', 'like', "%$search%")
                        );
                });
            }

            // Apply individual filters
            if (! empty($data['principal_list'])) {
                $query->whereIn('principal_id', $data['principal_list']);
            }
            if (! empty($data['category_list'])) {
                $query->whereIn('category_id', $data['category_list']);
            }
            if (! empty($data['brand_list'])) {
                $query->whereIn('brand_id', $data['brand_list']);
            }
            if (! empty($data['branch_list'])) {
                $query->whereIn('branch_id', $data['branch_list']);
            }

            // Date filter
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
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
            // Get specific fields
            $data = $request->only(['id']);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:products,id',
            ]);

            // Return if fail
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            // Delete product
            $deleted = Product::where('id', $data['id'])->update(['deleted_at' => Carbon::now()]);

            // Return if fail to delete
            if (! $deleted) {
                return Utility::apiError('Failed to delete product.', [], 221);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully.', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error deleting product.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function uploadProductFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            ]);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            $uploaded = $request->file('file');
            if (! $uploaded || ! $uploaded->isValid()) {
                return Utility::apiError('Invalid uploaded file', [], 422);
            }

            // Ensure public/uploads exists
            $uploadDir = public_path('uploads');
            if (! file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Build filename and move into public/uploads
            $filename = 'product_upload_'.time().'_'.Str::random(8).'.'.$uploaded->getClientOriginalExtension();
            $relativePath = 'uploads/'.$filename;
            $fullPath = public_path($relativePath);
            $uploaded->move($uploadDir, $filename);

            if (! file_exists($fullPath)) {
                return Utility::apiError('Failed to save file to public/uploads', [], 500);
            }

            // Quick header check using toArray (no heavy processing)
            $sheets = Excel::toArray([], $fullPath);
            if (empty($sheets) || ! isset($sheets[0]) || count($sheets[0]) === 0) {
                @unlink($fullPath);

                return Utility::apiError('Uploaded file is empty or unreadable', [], 221);
            }

            $rows = $sheets[0];
            $firstRow = $rows[0];
            $headers = array_map(function ($h) {
                return strtolower(trim((string) $h));
            }, $firstRow);

            $headers = array_map('strtolower', $headers);

            $required = ['part_no'];
            $optional = ['price', 'quantity'];

            // check for mandatory part_no
            $missing = array_diff($required, $headers);

            if (! empty($missing)) {
                @unlink($fullPath);

                return Utility::apiError(
                    'Invalid file header. Missing required: '.implode(', ', $missing),
                    221
                );
            }

            // check if at least one optional exists
            if (! array_intersect($optional, $headers)) {
                @unlink($fullPath);

                return Utility::apiError(
                    'Invalid file header. Either price or quantity must be present.',
                    222
                );
            }
            $totalRows = max(0, count($rows) - 1);

            $job = ImportJob::create([
                'file_name' => $filename,
                'file_path' => $relativePath,
                'status' => 'pending',
                'total_rows' => $totalRows,
                'processed_rows' => 0,
                'file_deleted' => false,
            ]);

            EnqueueProductImport::dispatch($fullPath, $job->id, $headers);

            return Utility::apiSuccess('File uploaded and queued for processing.', [
                'job_id' => $job->id,
            ]);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error uploading product.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function importStatus(Request $request)
    {

        try {
            $data = $request->only(['id']);

            if (empty($data['id'])) {
                return Utility::apiError('Priduct upload job id  not found', [], 422);
            }
            $job = ImportJob::find($data['id']);

            if (! $job) {
                return Utility::apiError('Job not found', [], 422);
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
