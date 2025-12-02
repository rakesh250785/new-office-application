<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\NewsLetterModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NewsLetterController extends Controller
{
    public function addUpdateNewsLetter(Request $request)
    {
        try {
            $rules = [
                'email' => 'required|email|max:191',
                'id' => 'nullable|integer|exists:news_letter,id',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Create or find existing
            $record = $request->filled('id')
                ? NewsLetterModel::find($request->input('id'))
                : new NewsLetterModel;

            if ($request->filled('id') && ! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->email = $request->input('email');
            $record->status = $request->has('status') ? (bool) $request->input('status') : ($record->status ?? true);
            $record->user_id = Auth::id() ?? null;
            $record->save();

            $msg = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($msg, $record, 200);
        } catch (Exception $ex) {
            Log::error('NewsLetter add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getNewsLetter(Request $request)
    {
        try {
            $data = $request->only(['id', 'page', 'per_page', 'search', 'start_date', 'end_date']);

            if (! empty($data['id'])) {
                $record = NewsLetterModel::find($data['id']);
                if (! $record) {
                    return Utility::apiError('NewsLetter not found', [], 404);
                }

                return Utility::apiSuccess('NewsLetter fetched successfully', $record, 200);
            }

            $page = max(1, (int) ($data['page'] ?? 1));
            $perPage = (int) ($data['per_page'] ?? config('constant.per_page', 15));
            $search = $data['search'] ?? null;
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;

            $query = NewsLetterModel::query();

            if (! empty($search)) {
                $query->where('email', 'like', '%'.$search.'%');
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('NewsLetter list fetched successfully', $paginator, 200);
        } catch (Exception $ex) {
            Log::error('NewsLetter get error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch newsletters', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteNewsLetter(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:news_letter,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = NewsLetterModel::find($request->id);
            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('NewsLetter delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting newsletter', ['exception' => $ex->getMessage()], 500);
        }
    }
}
