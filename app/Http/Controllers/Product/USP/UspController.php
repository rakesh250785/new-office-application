<?php

namespace App\Http\Controllers\Product\USP;

use App\Models\Usp;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\Export;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class UspController extends Controller
{
    public function __construct()
    {
    }
    public function addUpdateUSP(Request $request)
    {
        try {
            # Extract fields
            $data = $request->only([
                'usp_type',
                'packing_details',
                'usp_brand',
                'category_id',
                'principal_id',
                'usp_id',
            ]);

            # Validation rules
            $validator = Validator::make($data, [
                'usp_type' => 'required',
                'packing_details' => 'required',
                'usp_brand' => 'required',
                'category_id' => 'required|integer|exists:category_types,id',
                'principal_id' => 'required|integer|exists:principals,id',
                'usp_id' => 'nullable|integer|exists:usps,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Data mapping
            $arr = [
                'usp_type' => $data['usp_type'],
                'packing_details' => $data['packing_details'],
                'usp_brand' => $data['usp_brand'],
                'category_id' => $data['category_id'],
                'principal_id' => $data['principal_id'],
                'user_id' => Auth::id(),
                'branch_id' => Auth::user()['branch_id'],
            ];

            # Update or create
            $format = Usp::updateOrCreate(
                ['id' => $data['usp_id'] ?? null],
                $arr
            );

            # Return if fail
            if (!$format) {
                return Utility::apiError('Failed to save usp', [], 221);
            }

            # Message define
            $message = $data['usp_id']
                ? ' updated successfully'
                : ' created successfully';

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in usp format', ['exception' => $ex->getMessage()]);
        }
    }

    public function getUSP(Request $request)
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
            $query = Usp::with([
                'branch' => function ($q) {
                    $q->select('id', 'name');
                },
                'principal' => function ($q) {
                    $q->select('id', 'type');
                },
                'categoryType' => function ($q) {
                    $q->select('id', 'type');
                }
            ])->whereNull('deleted_at');

            # Global free-text search
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('usp_type', 'like', "%$search%")
                        ->orWhere('packing_details', 'like', "%$search%")
                        ->orWhere('usp_brand', 'like', "%$search%")

                        ->orWhereHas('branch', function ($b) use ($search) {
                            $b->where('name', 'like', "%$search%");
                        })
                        ->orWhereHas('principal', function ($b) use ($search) {
                            $b->where('type', 'like', "%$search%");
                        })
                        ->orWhereHas('categoryType', function ($b) use ($search) {
                            $b->where('type', 'like', "%$search%");
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
                    'usp_type' => 'Usp Type',
                    'packing_details' => 'Packing Details',
                    'usp_brand' => 'USP Brand',
                    'principal.type' => 'Principal',
                    'categoryType.type' => 'Category Type',
                    'branch.name' => 'Branch Name',
                    'created_at' => 'Date',
                ];

                $filename = 'usp' . now()->format('Ymd_His') . '.xlsx';
                return Excel::download(new Export($query, $columns), $filename);
            }

            # Pagination
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $usp = $query->orderByDesc('id')->paginate($perPage);

            # Retunr response
            return Utility::apiSuccess('List Quotation Format', $usp, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in usp', [
                'exception' => $ex->getMessage()
            ]);
        }
    }

    public function deleteUSP(Request $request)
    {
        try {
            # Get requested fields
            $data = $request->only(['id']);

            # Validate fields
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:usps,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Delete courier
            $records = Usp::where('id', $data['id'])->delete();
            if (!$records) {
                return Utility::apiError('Fail to delete usp !', [], 221);
            }

            # Return response
            return Utility::apiSuccess('deleted successfully!', [], 200);
        } catch (Exception $ex) {
            Log::debug('Quiotation delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting usp.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
