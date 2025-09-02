<?php

namespace App\Http\Controllers\Product\Category;

use App\Exports\CategoryExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Category as Categories;
use App\Models\Parameter;
use Exception;

class CategoryController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateCategory(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only([
                'name',
                'description',
                'parameter_field_id',
                'category_id'
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'parameter_field_id' => 'required|array',
                'category_id' => 'nullable|numeric|exists:categories,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Convert params fields into string
            $paramas = null;
            if (count($data['parameter_field_id']) > 0) {
                $paramas = implode(',', $data['parameter_field_id']);
            }
            # Payload
            $payload = [
                'name' => $data['name'],
                'description' => $data['description'],
                'parameter_field' => $paramas,
                'branch_id' => Auth::user()['branch_id'],
                'user_id' => Auth::user()['id'],
            ];

            # Update or create category
            $category = Categories::updateOrCreate(
                ['id' => $data['category_id'] ?? null],
                $payload
            );

            # Retunr if fails
            if (!$category) {
                return Utility::apiError('Failed to save category', [], 221);
            }

            # Handle message
            $message = !empty($data['category_id'])
                ? 'updated successfully'
                : 'created successfully';

            # Return response
            return Utility::apiSuccess($message, $category, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error in addUpdateCategory', ['exception' => $ex->getMessage()]);
        }
    }

    public function getCategory(Request $request)
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

            # Export logic
            if (!empty($data['download'])) {
                $columns = [
                    'name' => 'Category Name',
                    'description' => 'Description',
                    'parameter_fields' => 'Parameters',
                    'branch.name' => 'Branch',
                    'created_at' => 'Date',
                ];

                $filename = 'category_' . now()->format('Ymd_His') . '.xlsx';

                (new CategoryExport($data, $columns, Categories::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }


            # Base query
            $query = Categories::with('branch:id,name')
                ->whereNull('deleted_at');

            # Search logic including parameter_name
            if (!empty($data['search'])) {
                $search = $data['search'];
                $paramIds = Parameter::where('parameter_name', 'like', "%$search%")
                    ->pluck('id')
                    ->toArray();

                $query->where(function ($q) use ($search, $paramIds) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%")
                        ->orWhereHas('branch', fn($b) => $b->where('name', 'like', "%$search%"));

                    if (!empty($paramIds)) {
                        foreach ($paramIds as $id) {
                            $q->orWhereRaw("FIND_IN_SET(?, parameter_field)", [$id]);
                        }
                    }
                });
            }

            # Branch filter
            if (!empty($data['branch_list'])) {
                $query->whereIn('branch_id', $data['branch_list']);
            }

            # Date range filter
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            # Paginated API response
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $paginator = $query->orderByDesc('id')->paginate($perPage);

            # Transform parameter fields for API
            $items = $paginator->getCollection();
            $paramIds = $items->flatMap(fn($row) => explode(',', $row->parameter_field))
                ->filter()
                ->unique();

            $paramMap = Parameter::whereIn('id', $paramIds)->pluck('parameter_name', 'id');

            $items->transform(function ($row) use ($paramMap) {
                $ids = array_map('intval', array_map('trim', explode(',', $row->parameter_field)));
                $row->parameter_field_id = $ids;
                $row->parameter_fields = collect($ids)
                    ->map(fn($id) => $paramMap[(string) $id] ?? null)
                    ->filter()
                    ->implode(', ');
                return $row;
            });

            return Utility::apiSuccess('Category list fetched successfully', $paginator, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to fetch category', [
                'exception' => $ex->getMessage()
            ]);
        }
    }



    public function deleteCategory(Request $request)
    {
        try {

            # Request id
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:categories,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Soft delete record
            $deleted = Categories::where('id', $data['id'])->delete();

            # Retunr if fail
            if (!$deleted) {
                return Utility::apiError('Failed to delete category', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('category delete error: ' . $ex->getMessage());
            return Utility::apiError('Something went wrong while deleting category.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
