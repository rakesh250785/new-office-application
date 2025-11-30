<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\FeedbackModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Get feedback list or single record.
     * Supports: page, per_page, start_date, end_date, download, search, id
     */

     
    public function getFeedback(Request $request)
    {
        try {
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'search',
                'id',
            ]);

            // If single record requested
            if (! empty($data['id'])) {
                $record = FeedbackModel::find($data['id']);
                if (! $record) {
                    return Utility::apiError('Feedback not found', [], 404);
                }

                return Utility::apiSuccess('Feedback fetched successfully', $record, 200);
            }

            // Base query
            $query = FeedbackModel::query();

            // Free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            }

            // Date range filter
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            // Pagination
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $list = $query->orderByDesc('id')->paginate($perPage);

            return Utility::apiSuccess('Feedback list fetched successfully', $list, 200);
        } catch (Exception $ex) {
            Log::error('Feedback fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch feedback', ['exception' => $ex->getMessage()]);
        }
    }

    /**
     * Soft delete feedback.
     * Accepts: id
     */
    public function deleteFeedback(Request $request)
    {
        try {
            $data = $request->only(['id']);

            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:feedback_models,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $deleted = FeedbackModel::where('id', $data['id'])->delete();

            if (! $deleted) {
                return Utility::apiError('Failed to delete feedback', [], 221);
            }

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Feedback delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting feedback.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
