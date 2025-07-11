<?php

namespace App\Http\Controllers\Product\Category;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Auth;
class CategoryController extends Controller
{
    public function __construct(){
    }

    public function addUpdateCategory(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['name', 'description', 'product_param', 'category_id']);

            # Validate rule
            $validator = Validator::make($data, [
                'name' => 'required',
                'description' => 'required',
                'product_param' => 'required',
                'category_id' => 'required',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            $param = $data['product_param'];
            if (!empty($data['product_param'])) {
                $param = implode(',', $data['product_param']);
            }

            # Create category
            $arr = [
                'name' => $request->category_name,
                'description' => $request->category_desc,
                'product_fields' => $param,
                'deleted_at' => null,
                'category_banner_image' => 'no image',
                'category_small_banner_image' => 'no image',
                'branch_id' => Auth::user()->branch_id,
                'user_id' => Auth::user()->id,
                'status' => true,
            ];

            # Update notification
            if (!empty($data['category_id'])) {
                $status = Category::where('id', $data['category_id'])->update($arr);
                $message = 'Notification updated successfully';
            }

            # Add notification
            $status = Category::create($arr);
            if (!$status) {
                return Utility::apiError('Fail to add category', [], 221);
            }

            # Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::debug($ex);
            return Utility::apiError('Error occurred while action on addUpdateCategory', ['exception' => $ex->getMessage()]);
        }
    }

    public function getCategory(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only(['page', 'per_page', 'search']);

            # Get category info

            $category = Category::whereNull('deleted_at')->orderBy('id', 'desc')->paginate(10);

            # Return response
            return Utility::apiSuccess('List category', $category, 200);
        } catch (Exception $ex) {
            Log::debug($ex);
            return Utility::apiError('Error occurred while action on addUpdateCategory', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteCategory(Request $request)
    {
        try {

            # Request specific fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required',
            ]);

            # Return validation error 
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete principal
            $status = Category::where('id', $data['id'])->delete();
            if (!$status) {
                return Utility::apiError('Fail to delete category', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Category deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::debug($ex);
            return Utility::apiError('Error occurred while action on deleteCategory', ['exception' => $ex->getMessage()]);
        }
    }
}
