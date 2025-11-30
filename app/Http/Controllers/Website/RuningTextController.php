<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\RunningTextModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Log;

class RuningTextController extends Controller
{
    public function addUpdateRuningText(Request $request)
    {
        try {
            $data = $request->only(['content', 'id']);

            $validator = Validator::make($data, [
                'content' => 'required|string',
                'id' => 'nullable|integer|exists:runing_text,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $payload = [
                'content' => $data['content'],
                'user_id' => Auth::id() ?? null,
            ];

            $record = RunningTextModel::updateOrCreate(
                ['id' => $data['id'] ?? null],
                $payload
            );

            if (! $record) {
                return Utility::apiError('Failed to save running text', [], 221);
            }

            $message = isset($data['id']) ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($message, $record, 200);
        } catch (Exception $ex) {
            Log::error('Running text add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getRuningText(Request $request)
    {
        try {
            $data = $request->only(['id', 'page', 'per_page', 'search', 'start_date', 'end_date']);

            if (! empty($data['id'])) {
                $record = RunningTextModel::find($data['id']);
                if (! $record) {
                    return Utility::apiError('Running text not found', [], 404);
                }

                return Utility::apiSuccess('Running text fetched successfully', $record, 200);
            }

            $query = RunningTextModel::query();

            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where('content', 'like', "%{$search}%");
            }

            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $list = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('Running text list fetched successfully', $list, 200);
        } catch (Exception $ex) {
            Log::error('Running text fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch running text', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteRuningText(Request $request)
    {
        try {
            $data = $request->only(['id']);

            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:runing_text,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $deleted = RunningTextModel::where('id', $data['id'])->delete();

            if (! $deleted) {
                return Utility::apiError('Failed to delete running text', [], 221);
            }

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Running text delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting running text.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
