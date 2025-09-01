<?php

namespace App\Http\Controllers\Vendor\Supplier;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class SupplierController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateSupplier(Request $request)
    {
        try {
            # Extract only expected fields
            $data = $request->only([
                'product_id',
                'date',
                'product_list',
                'update_status',
                'supplier_id',
                'principal_id'
            ]);

            # Validate input
            $validator = Validator::make($data, [
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'principal_id' => ['required', 'integer', 'exists:principals,id'],
                'date' => ['required', 'date'],
                'product_list' => ['required', 'array', 'min:1'],
                'product_list.*.id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'product_list.*.currency_id' => ['required', 'integer', 'exists:currencies,id'],
                'product_list.*.source_id' => ['required', 'integer', 'exists:sources,id'],
                'product_list.*.rate_fc' => ['required', 'numeric'],
                'product_list.*.factor_fc' => ['required', 'numeric'],
                'product_list.*.total_cost' => ['required', 'numeric'],
                'product_list.*.discount' => ['required', 'numeric'],
                'product_list.*.net_price' => ['required', 'numeric'],
                'product_list.*.custom_price' => ['required', 'numeric'],
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Context setup
            $branchId = Auth::user()->branch_id;
            $userId = Auth::id();
            $date = Carbon::parse($data['date'])->format('Y-m-d');

            # Track all submitted IDs
            $submittedIds = [];

            foreach ($data['product_list'] as $item) {
                $supplier = Supplier::updateOrCreate(
                    ['id' => $item['id'] ?? 0],
                    [
                        'product_id' => $data['product_id'],
                        'principal_id' => $data['principal_id'],
                        'source_id' => $item['source_id'],
                        'currency_id' => $item['currency_id'],
                        'rate_fc' => $item['rate_fc'],
                        'factor_fc' => $item['factor_fc'],
                        'total_cost' => round($item['total_cost']),
                        'discount' => $item['discount'],
                        'net_price' => round($item['net_price']),
                        'custom_price' => round($item['custom_price']),
                        'user_id' => $userId,
                        'branch_id' => $branchId,
                        'deleted_at' => null,
                        'date' => $date,
                    ]
                );

                # Collect actual DB id (new or updated)
                $submittedIds[] = $supplier->id;
            }

            # Delete missing suppliers from DB for this product and branch
            Supplier::where('product_id', $data['product_id'])
                ->where('branch_id', $branchId)
                ->whereNotIn('id', $submittedIds)
                ->delete();

            # Return success
            return Utility::apiSuccess('Data saved successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error while saving supplier', ['exception' => $ex->getMessage()]);
        }
    }


    public function getSupplier(Request $request)
    {
        try {
            $data = $request->only([
                'page',
                'per_page',
                'owner',
                'branch',
                'principal',
                'source',
                'product',
                'currency',
                'search',
            ]);

            $page = max((int) ($data['page'] ?? 1), 1);
            $perPage = max((int) ($data['per_page'] ?? config('constant.per_page', 15)), 1);

            // Base query with joins
            $baseQuery = Supplier::query()
                ->join('products', 'products.id', '=', 'suppliers.product_id')
                ->whereNull('suppliers.deleted_at');

            // --- Apply filters ---
            if (!empty($data['owner'])) {
                $baseQuery->whereIn('suppliers.user_id', $data['owner']);
            }

            if (!empty($data['branch'])) {
                $baseQuery->whereIn('suppliers.branch_id', $data['branch']);
            }

            if (!empty($data['principal'])) {
                $baseQuery->whereIn('suppliers.principal_id', $data['principal']);
            }

            if (!empty($data['product'])) {
                $baseQuery->whereIn('suppliers.product_id', $data['product']);
            }

            if (!empty($data['source'])) {
                $baseQuery->whereIn('suppliers.source_id', $data['source']);
            }

            if (!empty($data['currency'])) {
                $baseQuery->whereIn('suppliers.currency_id', $data['currency']);
            }

            if (!empty($data['search'])) {
                $search = '%' . $data['search'] . '%';
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('products.part_no', 'like', $search)
                        ->orWhere('products.description', 'like', $search);
                });
            }

            // Step 1: Paginate distinct part_no
            $partNoListQuery = (clone $baseQuery)
                ->select('products.part_no')
                ->distinct();

            $totalGroups = DB::table(DB::raw("({$partNoListQuery->toSql()}) as grouped"))
                ->mergeBindings($partNoListQuery->getQuery())
                ->count();

            $partNos = DB::table(DB::raw("({$partNoListQuery->toSql()}) as grouped"))
                ->mergeBindings($partNoListQuery->getQuery())
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->pluck('part_no');

            // Step 2: Fetch full supplier data
            $supplierQuery = Supplier::query()
                ->select([
                    'id',
                    'product_id',
                    'branch_id',
                    'user_id',
                    'currency_id',
                    'principal_id',
                    'source_id',
                    'rate_fc',
                    'factor_fc',
                    'total_cost',
                    'discount',
                    'net_price',
                    'custom_price',
                    'date',
                    'deleted_at',
                    'created_at',
                    'updated_at'
                ])
                ->whereNull('deleted_at')
                ->whereHas('product', function ($q) use ($partNos) {
                    $q->whereIn('part_no', $partNos);
                })
                ->with([
                    'product:id,part_no,description',
                    'principal:id,type',
                    'source:id,name',
                    'currency:id,name',
                    'branch:id,name'
                ])
                ->orderByDesc('id');

            // Apply filters again
            if (!empty($data['owner'])) {
                $supplierQuery->whereIn('user_id', $data['owner']);
            }

            if (!empty($data['branch'])) {
                $supplierQuery->whereIn('branch_id', $data['branch']);
            }

            if (!empty($data['principal'])) {
                $supplierQuery->whereIn('principal_id', $data['principal']);
            }

            if (!empty($data['product'])) {
                $supplierQuery->whereIn('product_id', $data['product']);
            }

            if (!empty($data['source'])) {
                $supplierQuery->whereIn('source_id', $data['source']);
            }

            if (!empty($data['currency'])) {
                $supplierQuery->whereIn('currency_id', $data['currency']);
            }

            if (!empty($data['search'])) {
                $search = '%' . $data['search'] . '%';
                $supplierQuery->whereHas('product', function ($q) use ($search) {
                    $q->where('part_no', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            }

            // Step 3: Group by part_no
            $result = $supplierQuery->get()->groupBy(fn($item) => $item->product->part_no);

            return Utility::apiSuccess('Supplier list grouped by part_no', [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalGroups,
                'last_page' => ceil($totalGroups / $perPage),
                'data' => $result,
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error fetching supplier list', ['exception' => $ex->getMessage()]);
        }
    }



    public function deleteSupplier(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => ['required', 'integer', 'exists:products,id'],
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete supplier
            $deleted = Supplier::where('product_id', $data['id'])->delete();

            # Return if fail to delete
            if (!$deleted) {
                return Utility::apiError('Failed to delete supplier', [], 400);
            }

            # Return response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error while deleting supplier', ['exception' => $ex->getMessage()]);
        }
    }
}
