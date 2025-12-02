<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\SocialMediaModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SocialMediaController extends Controller
{
    public function addUpdateSocialMedia(Request $request)
    {
        try {
            $rules = [
                'type' => 'nullable|string|max:128',
                'content' => 'nullable|string',
                'status' => 'nullable|boolean',
                'id' => 'nullable|integer|exists:social_media,id',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = $request->filled('id') ? SocialMediaModel::find($request->input('id')) : new SocialMediaModel;

            if ($request->filled('id') && ! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->type = $request->input('type');
            $record->content = $request->input('content');
            $record->status = $request->has('status') ? (bool) $request->input('status') : ($record->status ?? true);
            $record->user_id = Auth::id() ?? $record->user_id ?? null;
            $record->save();

            $msg = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($msg, $record, 200);
        } catch (Exception $ex) {
            Log::error('SocialMedia add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }
   
    public function getSocialMedia(Request $request)
    {
        try {
            $data = $request->only(['id', 'page', 'per_page', 'q', 'start_date', 'end_date', 'status']);

            $page = max(1, (int) ($data['page'] ?? 1));
            $perPage = (int) ($data['per_page'] ?? config('constant.per_page', 15));
            $q = isset($data['q']) ? trim($data['q']) : null;
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;
            $status = $data['status'] ?? null; // 0|1|null

            $query = SocialMediaModel::query();

            if (! empty($q)) {
                $query->where(function ($qb) use ($q) {
                    $qb->where('type', 'like', '%'.$q.'%')
                        ->orWhere('content', 'like', '%'.$q.'%');
                });
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            if ($status !== null && ($status === '0' || $status === '1' || $status === 0 || $status === 1)) {
                $query->where('status', (bool) $status);
            }

            $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Social media list fetched successfully', $paginator, 200);
        } catch (Exception $ex) {
            Log::error('SocialMedia get error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch social media', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteSocialMedia(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:social_media,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = SocialMediaModel::find($request->id);
            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('SocialMedia delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting social media', ['exception' => $ex->getMessage()], 500);
        }
    }
}
