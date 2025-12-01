<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\OurTeamModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Log;
use Storage;
use Str;
use Validator;

class OurTeamController extends Controller
{
    /**
     * Add / Update team member
     */
    public function addUpdateOurTeam(Request $request)
    {
        try {
            $rules = [
                'id' => 'nullable|integer|exists:our_team,id',
                'name' => 'required|string|max:191',
                'designation' => 'nullable|string|max:191',
                'quote' => 'nullable|string',
                'image' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            ];

            $validator = Validator::make($request->all(), $rules);

            // Require image only on create
            if (! $request->filled('id') && ! $request->hasFile('image')) {
                $validator->after(function ($v) {
                    $v->errors()->add('image', 'Image is required for new team member.');
                });
            }

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Create or find existing
            $record = $request->filled('id')
                ? OurTeamModel::find($request->id)
                : new OurTeamModel;

            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->name = $request->name;
            $record->designation = $request->designation;
            $record->quote = $request->quote;
            $record->user_id = Auth::id() ?? null;

            // Image upload
            $imageUrl = null;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext = $file->getClientOriginalExtension();
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.time().'.'.$ext;

                Storage::disk('public')->putFileAs('ourTeam', $file, $filename);

                // Delete old image
                if ($record->exists && $record->image) {
                    $old = 'ourTeam/'.$record->image;
                    if (Storage::disk('public')->exists($old)) {
                        Storage::disk('public')->delete($old);
                    }
                }

                $record->image = $filename;

                $imageUrl = Storage::disk('public')->url('ourTeam/'.$filename);
            } else {
                if (! empty($record->image)) {
                    $imageUrl = Storage::disk('public')->url('ourTeam/'.$record->image);
                }
            }

            $record->save();

            $msg = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($msg, [], 200);

        } catch (Exception $ex) {
            Log::error('OurTeam add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * List team members with filters
     */
    public function getOurTeam(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search', null);
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = OurTeamModel::query();

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('designation', 'like', '%'.$search.'%')
                        ->orWhere('quote', 'like', '%'.$search.'%');
                });
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            $data = $paginator->getCollection()->transform(function ($item) {
                $imageUrl = null;

                if ($item->image) {
                    $path = 'ourTeam/'.$item->image;

                    if (Storage::disk('public')->exists($path)) {
                        $imageUrl = Storage::disk('public')->url($path);
                    }
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'designation' => $item->designation,
                    'quote' => $item->quote,
                    'image' => $imageUrl,
                    'created_at' => $item->created_at,
                ];
            });

            $result = $paginator->toArray();
            $result['data'] = $data;

            return Utility::apiSuccess('Team list fetched successfully', $result, 200);

        } catch (Exception $ex) {
            Log::error('OurTeam list error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * Delete team member
     */
    public function deleteOurTeam(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:our_team,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = OurTeamModel::find($request->id);
            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            // delete image
            if ($record->image) {
                $path = 'ourTeam/'.$record->image;
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('OurTeam delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }
}
