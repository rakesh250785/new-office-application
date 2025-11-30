<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\AppLogoModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Log;

class AppLogoController extends Controller
{
    public function addUpdateAppLogo(Request $request)
    {
        try {
            $rules = [
                'id' => 'nullable|integer|exists:app_logo,id',
            ];

            if ($request->hasFile('image')) {
                $rules['image'] = 'file|max:51200';
            }

            $validator = Validator::make($request->all(), $rules);

            if (! $request->filled('id') && ! $request->hasFile('image')) {
                $validator->after(function ($v) {
                    $v->errors()->add('image', 'Image is required for new App Logo.');
                });
            }

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            if ($request->filled('id')) {
                $appLogo = AppLogoModel::find($request->input('id'));
                if (! $appLogo) {
                    return Utility::apiError('App logo not found', [], 404);
                }
            } else {
                $appLogo = new AppLogoModel;
            }

            $user = Auth::user();
            $branchId = $user->branch_id;
            $userId = $user->id;

            // handle file upload
            $fileUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // build safe filename
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext = $file->getClientOriginalExtension();
                $filename = \Illuminate\Support\Str::slug($originalName).'-'.time().'.'.$ext;

                Storage::disk('public')->putFileAs('appLogo', $file, $filename);

                if ($appLogo->exists && ! empty($appLogo->image)) {
                    $old = $appLogo->image;
                    $publicPath = public_path('appLogo/'.$old);
                    if (file_exists($publicPath)) {
                        @unlink($publicPath);
                    }
                    $storageRelative = 'appLogo/'.$old;
                    if (Storage::disk('public')->exists($storageRelative)) {
                        Storage::disk('public')->delete($storageRelative);
                    }
                }
                $appLogo->image = $filename;
                $publicPathCheck = public_path('appLogo/'.$filename);
                if (file_exists($publicPathCheck)) {
                    $fileUrl = url('appLogo/'.$filename);
                } else {
                    $fileUrl = Storage::disk('public')->url('appLogo/'.$filename);
                }
            } else {
                if (! empty($appLogo->image)) {
                    $dbFile = $appLogo->image;
                    if (filter_var($dbFile, FILTER_VALIDATE_URL)) {
                        $fileUrl = $dbFile;
                    } else {
                        $publicPathCheck = public_path('appLogo/'.$dbFile);
                        $storageRelative = 'appLogo/'.$dbFile;
                        if (file_exists($publicPathCheck)) {
                            $fileUrl = url('appLogo/'.$dbFile);
                        } elseif (Storage::disk('public')->exists($storageRelative)) {
                            $fileUrl = Storage::disk('public')->url($storageRelative);
                        } else {
                            Log::warning("AppLogo file missing for record: {$dbFile}");
                            $fileUrl = null;
                        }
                    }
                }
            }

            $appLogo->branch_id = $branchId;
            $appLogo->user_id = $userId;
            $appLogo->save();

            $message = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error('AppLogo add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getAppLogo(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search', null);
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = AppLogoModel::query();

            if (! empty($search)) {
                $query->where('branch_id', 'like', '%'.$search.'%');
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $paginator = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $transformed = $paginator->getCollection()->transform(function ($item) {

                $imageUrl = null;

                if (! empty($item->image)) {
                    $dbFile = $item->image;

                    // If already a URL
                    if (filter_var($dbFile, FILTER_VALIDATE_URL)) {
                        $imageUrl = $dbFile;
                    } else {
                        $publicPath = public_path('appLogo/'.$dbFile);
                        $storagePath = 'appLogo/'.$dbFile;

                        if (file_exists($publicPath)) {
                            $imageUrl = url('appLogo/'.$dbFile);
                        } elseif (Storage::disk('public')->exists($storagePath)) {
                            $imageUrl = Storage::disk('public')->url($storagePath);
                        } else {
                            Log::warning("AppLogo file missing for record {$item->id}: {$dbFile}");
                            $imageUrl = null;
                        }
                    }
                }

                return [
                    'id' => $item->id,
                    'image' => $imageUrl,
                    'branch_id' => $item->branch_id,
                    'user_id' => $item->user_id,
                    'created_at' => $item->created_at,
                ];
            })->values()->all();

            $paginated = $paginator->toArray();
            $paginated['data'] = $transformed;

            return Utility::apiSuccess('App logo list fetched successfully', $paginated, 200);

        } catch (Exception $ex) {
            Log::error('AppLogo get error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', [
                'exception' => $ex->getMessage(),
            ], 500);
        }
    }

    public function deleteAppLogo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:app_logo,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = AppLogoModel::find($request->id);

            if (! $record) {
                return Utility::apiError('App logo not found', [], 404);
            }

            // Correct folder name where file stored
            $filePath = 'appLogo/'.$record->image;

            // Delete file from public storage
            if (! empty($record->image) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            // Delete DB record
            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('AppLogo delete error: '.$ex->getMessage());

            return Utility::apiError(
                'Something went wrong while deleting app logo.',
                ['exception' => $ex->getMessage()],
                500
            );
        }
    }
}
