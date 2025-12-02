<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\ContactUsRequestModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactUsRequestController extends Controller
{
    public function getContactUsRequest(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search');

            $query = ContactUsRequestModel::query();

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")
                        ->orWhere('message', 'like', "%$search%");
                });
            }

            $result = $query->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('fetched successfully', $result, 200);

        } catch (Exception $ex) {
            Log::error('Contact Us Request fetch error: '.$ex->getMessage());

            return Utility::apiError(
                'Failed to fetch details',
                ['exception' => $ex->getMessage()],
                500
            );
        }
    }

    public function deleteContactUsRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:contact_us_requests,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = ContactUsRequestModel::find($request->id);

            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('Contact Us Request delete error: '.$ex->getMessage());

            return Utility::apiError(
                'Something went wrong while deleting',
                ['exception' => $ex->getMessage()],
                500
            );
        }
    }
}
