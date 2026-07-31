<?php

namespace App\Http\Controllers\Vendor\Supplier;

use App\Exports\SupplierExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function __construct() {}

    public function addUpdateSupplier(Request $request)
    {
        try {
            $data = $request->only([
                'product_id',
                'date',
                'product_list',
                'update_status',
                'supplier_id',
                'principal_id',
            ]);

            $validator = Validator::make($data, [
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'principal_id' => ['required', 'integer', 'exists:principals,id'],
                'date' => ['required', 'date'],
                'product_list' => ['required', 'array', 'min:1'],
                'product_list.*.id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'product_list.*.currency_id' => ['required', 'integer', 'exists:currencies,id'],
                'product_list.*.date' => ['nullable', 'date'],
                'product_list.*.source_id' => ['required', 'integer', 'exists:sources,id'],
                'product_list.*.rate_fc' => ['required', 'numeric'],
                'product_list.*.factor_fc' => ['required', 'numeric'],
                'product_list.*.total_cost' => ['required', 'numeric'],
                'product_list.*.discount' => ['required', 'numeric'],
                'product_list.*.net_price' => ['required', 'numeric'],
                'product_list.*.custom_price' => ['required', 'numeric'],
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $branchId = Auth::user()->branch_id;
            $userId = Auth::id();

            // Get all source IDs from request
            $sourceIds = collect($data['product_list'])->pluck('id')->toArray();

            if ($data['update_status']) {
                Supplier::where('product_id', $data['product_id'])
                    ->where('principal_id', $data['principal_id'])
                    ->whereNotIn('id', $sourceIds)
                    ->delete();
            }

            foreach ($data['product_list'] as $item) {

                // Decide date
                $productDate = $data['update_status']
                    ? $item['date']      // edit case
                    : $data['date'];     // add case

                Supplier::updateOrCreate(
                    [
                        'product_id' => $data['product_id'],
                        'principal_id' => $data['principal_id'],
                        'source_id' => $item['source_id'],
                    ],
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
                        'profit' => round($item['profit']),
                        'user_id' => $userId,
                        'branch_id' => $branchId,
                        'deleted_at' => null,
                        'date' => $productDate,
                    ]
                );
            }

            return Utility::apiSuccess('Data saved successfully', [], 200);

        } catch (Exception $ex) {

            Log::error($ex);

            return Utility::apiError('Error while saving supplier', [
                'exception' => $ex->getMessage(),
            ]);
        }
    }

    public function getSupplier(Request $request)
    {
        try {

            $page = max((int) $request->input('page', 1), 1);
            $perPage = max((int) $request->input('per_page', config('constant.per_page', 15)), 1);
            $search = $request->input('search', '');
            $data = $request->only(['search', 'download', 'column', 'per_page', 'start_date', 'end_date', 'principal', 'brand_list', 'source', 'currency', 'supplierId']);

            if (! empty($data['download'])) {
                $columns = [
                    'principal.type' => 'Principal',
                    'product.part_no' => 'Part No.',
                    'product.description' => 'Description',
                    'source.name' => 'Source',
                    'currency.name' => 'Currency',
                    'rate_fc' => 'Rate FC',
                    'factor_fc' => 'Factor FC',
                    'total_cost' => 'Total Cost',
                    'discount' => 'Discount',
                    'net_price' => 'Net Price',
                    'profit' => 'Profit',
                    'custom_price' => 'custom_price',
                    'created_at' => 'Date',
                ];

                $filename = 'supplier_'.now()->format('Ymd_His').'.xlsx';

                (new SupplierExport($data, $columns, Supplier::class, Auth::id()))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            $query = Supplier::query()
                ->with([
                    'product:id,part_no,description',
                    'principal:id,type',
                    'source:id,name',
                    'currency:id,name',
                    'branch:id,name',
                ])
                ->whereNull('suppliers.deleted_at')
                ->whereHas('product', function ($q) {
                    $q->whereNotNull('part_no')
                        ->where('part_no', '!=', '');
                });

            /*
            |--------------------------------------------------------------------------
            | FILTERS
            |--------------------------------------------------------------------------
            */

            $temp = $request->partId['current'] ?? null;

            if ($temp) {
                $query->where('suppliers.product_id', $temp);
            }

            if ($request->branch) {
                $query->whereIn('suppliers.branch_id', (array) $request->branch);
            }

            if ($request->principal) {
                $query->whereIn('suppliers.principal_id', (array) $request->principal);
            }

            if ($request->product) {
                $query->whereIn('suppliers.product_id', (array) $request->product);
            }

            if ($request->source) {
                $query->whereIn('suppliers.source_id', (array) $request->source);
            }

            if ($request->currency) {
                $query->whereIn('suppliers.currency_id', (array) $request->currency);
            }

            if ($request->start_date && $request->end_date) {
                $query->whereBetween('suppliers.date', [$request->start_date, $request->end_date]);
            }

            if (Utility::checkViewPermission('supplier')) {
                $query->where('suppliers.user_id', Auth::id());
            }

            /*
            |--------------------------------------------------------------------------
            | SEARCH
            |--------------------------------------------------------------------------
            */

            if ($search !== '') {

                $query->where(function ($q) use ($search) {

                    $q->whereHas('product', function ($q2) use ($search) {
                        $q2->where('part_no', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%");
                    })
                        ->orWhere('suppliers.rate_fc', 'like', "%$search%")
                        ->orWhere('suppliers.factor_fc', 'like', "%$search%")
                        ->orWhere('suppliers.total_cost', 'like', "%$search%")
                        ->orWhere('suppliers.discount', 'like', "%$search%")
                        ->orWhere('suppliers.net_price', 'like', "%$search%")
                        ->orWhere('suppliers.custom_price', 'like', "%$search%")
                        ->orWhereHas('principal', fn ($q2) => $q2->where('type', 'like', "%$search%"))
                        ->orWhereHas('source', fn ($q2) => $q2->where('name', 'like', "%$search%"))
                        ->orWhereHas('currency', fn ($q2) => $q2->where('name', 'like', "%$search%"))
                        ->orWhereHas('branch', fn ($q2) => $q2->where('name', 'like', "%$search%"));
                });
            }

            /*
            |--------------------------------------------------------------------------
            | FETCH DATA
            |--------------------------------------------------------------------------
            */

            $suppliers = $query
                ->orderByDesc('suppliers.id')
                ->get()
                ->groupBy('product.part_no');

            /*
            |--------------------------------------------------------------------------
            | PAGINATION ON GROUPS
            |--------------------------------------------------------------------------
            */

            $totalGroups = $suppliers->count();

            $pagedData = $suppliers
                ->slice(($page - 1) * $perPage, $perPage);

            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */

            return Utility::apiSuccess(
                'Supplier list grouped by part_no',
                [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalGroups,
                    'last_page' => ceil($totalGroups / $perPage),
                    'data' => $pagedData,
                ],
                200
            );

        } catch (Exception $ex) {

            Log::error($ex);

            return Utility::apiError(
                'Error fetching supplier list',
                ['exception' => $ex->getMessage()]
            );
        }
    }

    public function deleteSupplier(Request $request)
    {
        try {
            // Get specific fields
            $data = $request->only(['id']);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => ['required', 'integer', 'exists:products,id'],
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            // Delete supplier
            $deleted = Supplier::where('product_id', $data['id'])->delete();

            // Return if fail to delete
            if (! $deleted) {
                return Utility::apiError('Failed to delete supplier', [], 400);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error while deleting supplier', ['exception' => $ex->getMessage()]);
        }
    }
}
