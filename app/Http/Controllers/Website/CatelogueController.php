<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\CatelogueModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Log;

class CatelogueController extends Controller
{
    public function addUpdateCatelogue(Request $request)
    {
        try {
            // Validation: all file types allowed, size limit 50MB
            $rules = [
                'name' => 'required|string|max:191',
                'file' => 'sometimes|file|max:51200',
                'id' => 'nullable|integer|exists:catelogue,id',
            ];

            $validator = Validator::make($request->all(), $rules);

            // require file on create (use filled to detect id value)
            if (! $request->filled('id') && ! $request->hasFile('file')) {
                $validator->after(function ($v) {
                    $v->errors()->add('file', 'File is required for new catalogue.');
                });
            }

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // create or update model
            if ($request->filled('id')) {
                $catelogue = CatelogueModel::find($request->input('id'));
                if (! $catelogue) {
                    return Utility::apiError('Catalogue not found', [], 404);
                }
            } else {
                $catelogue = new CatelogueModel;
            }

            $catelogue->name = $request->input('name');
            $catelogue->user_id = auth()->id() ?? null;

            $fileUrl = null;

            // handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $ext = $file->getClientOriginalExtension();
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.time().'.'.$ext;

                // store in storage/app/public/catelogues
                Storage::disk('public')->putFileAs('catelogues', $file, $filename);

                // delete old file if exists (public folder and storage disk)
                if ($catelogue->exists && ! empty($catelogue->file)) {
                    $old = $catelogue->file;

                    // try public/catelogues/<old>
                    $publicPath = public_path('catelogues/'.$old);
                    if (file_exists($publicPath)) {
                        @unlink($publicPath);
                    }

                    // try storage/app/public/catelogues/<old>
                    $storageRelative = 'catelogues/'.$old;
                    if (Storage::disk('public')->exists($storageRelative)) {
                        Storage::disk('public')->delete($storageRelative);
                    }
                }

                // store only filename in DB (consistent with profile style)
                $catelogue->file = $filename;

                // resolve file url (prefer public path if present, else storage disk url)
                $publicPathCheck = public_path('catelogues/'.$filename);
                if (file_exists($publicPathCheck)) {
                    $fileUrl = url('catelogues/'.$filename);
                } else {
                    $fileUrl = Storage::disk('public')->url('catelogues/'.$filename);
                }
            } else {
                // if no new file uploaded but DB has a filename, resolve its URL for response
                if (! empty($catelogue->file)) {
                    $dbFile = $catelogue->file;
                    if (filter_var($dbFile, FILTER_VALIDATE_URL)) {
                        $fileUrl = $dbFile;
                    } else {
                        $publicPathCheck = public_path('catelogues/'.$dbFile);
                        $storageRelative = 'catelogues/'.$dbFile;
                        if (file_exists($publicPathCheck)) {
                            $fileUrl = url('catelogues/'.$dbFile);
                        } elseif (Storage::disk('public')->exists($storageRelative)) {
                            $fileUrl = Storage::disk('public')->url($storageRelative);
                        } else {
                            Log::warning("Catalogue file missing for record (no upload): {$dbFile}");
                            $fileUrl = null;
                        }
                    }
                }
            }

            $catelogue->save();

            $message = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error('Catelogue add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCatelogue(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search', null);
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = CatelogueModel::query();

            if (! empty($search)) {
                $query->where('name', 'like', '%'.$search.'%');
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

            $transformed = $paginator->getCollection()->transform(function ($item) {
                $fileUrl = null;
                if (! empty($item->file)) {
                    $dbFile = $item->file;
                    if (filter_var($dbFile, FILTER_VALIDATE_URL)) {
                        $fileUrl = $dbFile;
                    } else {
                        $publicPath = public_path('catelogues/'.$dbFile);
                        $storageRelative = 'catelogues/'.$dbFile;

                        if (file_exists($publicPath)) {
                            $fileUrl = url('catelogues/'.$dbFile);
                        } elseif (Storage::disk('public')->exists($storageRelative)) {
                            $fileUrl = Storage::disk('public')->url($storageRelative);
                        } else {
                            Log::warning("Catalogue file missing for record {$item->id}: {$dbFile}");
                            $fileUrl = null;
                        }
                    }
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'file' => $fileUrl,
                    'created_at' => $item->created_at,
                ];
            })->values()->all();

            $pagArray = $paginator->toArray();
            $pagArray['data'] = $transformed;

            return Utility::apiSuccess('Catalogue list fetched successfully', $pagArray, 200);

        } catch (Exception $ex) {
            Log::error('Catelogue get error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteCatelogue(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:app_logo,id',
            ]);

            

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = CatelogueModel::find($request->id);

            if (! $record) {
                return Utility::apiError('App logo not found', [], 404);
            }

            $filePath = 'catelogues/'.$record->image;

            if (! empty($record->image) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('deleteCatelogue delete error: '.$ex->getMessage());

            return Utility::apiError(
                'Something went wrong while deleting app logo.',
                ['exception' => $ex->getMessage()],
                500
            );
        }
    }
}
