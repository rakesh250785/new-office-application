<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\FqaModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Log;

class FaqController extends Controller
{
    public function addUpdateFqa(Request $request)
    {
        try {

            logger('keeter');
            // Extract input
            $data = $request->only([
                'question',
                'answer',
                'id',
            ]);

            // Validation rules
            $validator = Validator::make($data, [
                'question' => 'required|string|max:255',
                'answer' => 'required|string|max:255',
                'id' => 'nullable',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Prepare payload
            $payload = [
                'question' => $data['question'],
                'answer' => $data['answer'],
                'user_id' => Auth::id() ?? null,
            ];

            // Create or update record
            $faq = FqaModel::updateOrCreate(
                ['id' => $data['id'] ?? null],
                $payload
            );

            // Return if fail
            if (! $faq) {
                return Utility::apiError('Failed to save faq', [], 221);
            }

            // Prepare message
            $message = isset($data['id']) ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error('Faq add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()]);
        }
    }

    public function getFaq(Request $request)
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
                $record = FqaModel::find($data['id']);
                if (! $record) {
                    return Utility::apiError('Feedback not found', [], 404);
                }

                return Utility::apiSuccess('Feedback fetched successfully', $record, 200);
            }

            // Base query
            $query = FqaModel::query();

            // Free-text search
            if (! empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('question', 'like', "%{$search}%")
                        ->orWhere('answer', 'like', "%{$search}%");
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

    public function deleteFaq(Request $request)
    {
        try {
            $data = $request->only(['id']);

            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:feedback_models,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $deleted = FqaModel::where('id', $data['id'])->delete();

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
