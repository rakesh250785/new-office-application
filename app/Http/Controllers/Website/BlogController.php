<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\BlogModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function addUpdateBlog(Request $request)
    {
        try {
            $rules = [
                'id' => 'nullable|integer|exists:blog,id',
                'date' => 'required|date',
                'title' => 'required|string|max:191',
                'heading' => 'required|string|max:191',
                'content' => 'nullable|string',
                'image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            ];

            $validator = Validator::make($request->all(), $rules);

            if (! $request->filled('id') && ! $request->hasFile('image')) {
                $validator->after(function ($v) {
                    $v->errors()->add('image', 'Image is required for new gallery item.');
                });
            }

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = $request->filled('id') ? BlogModel::find($request->input('id')) : new BlogModel;

            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            // assign simple fields
            $record->date = $request->input('date') ? date('Y-m-d', strtotime($request->input('date'))) : null;
            $record->title = $request->input('title');
            $record->heading = $request->input('heading');
            $record->content = $request->input('content');
            $record->user_id = Auth::id() ?? null;

            // handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext = $file->getClientOriginalExtension();
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.time().'.'.$ext;

                Storage::disk('public')->putFileAs('blog', $file, $filename);

                if ($record->exists && ! empty($record->image)) {
                    $oldPath = 'blog/'.$record->image;
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    } else {
                        $publicPath = public_path('blog/'.$record->image);
                        if (file_exists($publicPath)) {
                            @unlink($publicPath);
                        }
                    }
                }

                // save filename in DB
                $record->image = $filename;
            }

            $record->save();

            $msg = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            if (! empty($record->image)) {
                $path = 'blog/'.$record->image;
                if (Storage::disk('public')->exists($path)) {
                    $imageUrl = Storage::disk('public')->url($path);
                } else {
                    $publicPath = public_path('blog/'.$record->image);
                    if (file_exists($publicPath)) {
                        $imageUrl = url('blog/'.$record->image);
                    }
                }
            }

            return Utility::apiSuccess($msg, [], 200);

        } catch (Exception $ex) {
            Log::error('Blog add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * List gallery items with filters and pagination
     */
    public function getBlog(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search', null);
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = BlogModel::query();

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%'.$search.'%')
                        ->orWhere('heading', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%');
                });
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            $transformed = $paginator->getCollection()->transform(function ($item) {
                $imageUrl = null;

                if (! empty($item->image)) {
                    $path = 'blog/'.$item->image;
                    if (Storage::disk('public')->exists($path)) {
                        $imageUrl = Storage::disk('public')->url($path);
                    } else {
                        $publicPath = public_path('blog/'.$item->image);
                        if (file_exists($publicPath)) {
                            $imageUrl = url('blog/'.$item->image);
                        }
                    }
                }

                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'title' => $item->title,
                    'heading' => $item->heading,
                    'content' => $item->content,
                    'image' => $imageUrl,
                    'created_at' => $item->created_at,
                ];
            })->values()->all();

            $pagArray = $paginator->toArray();
            $pagArray['data'] = $transformed;

            return Utility::apiSuccess('Gallery list fetched successfully', $pagArray, 200);

        } catch (Exception $ex) {
            Log::error('Blog get error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * Delete gallery item
     */
    public function deleteBlog(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:blog,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = BlogModel::find($request->input('id'));
            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            // delete image file (if exists)
            if (! empty($record->image)) {
                $path = 'blog/'.$record->image;
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                } else {
                    $publicPath = public_path('blog/'.$record->image);
                    if (file_exists($publicPath)) {
                        @unlink($publicPath);
                    }
                }
            }

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('Blog delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }
}
