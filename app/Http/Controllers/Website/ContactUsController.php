<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\ContactUsModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    public function addUpdateContactUs(Request $request)
    {
        try {
            $rules = [
                'id' => 'nullable|integer|exists:contact_us,id',    
                'location' => 'required|string|max:255',
                'address' => 'required|string|max:1000',
                'contact' => 'required|string|max:100',
                'email' => 'required|email|max:255',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = $request->filled('id') ? ContactUsModel::find($request->input('id')) : new ContactUsModel;

            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->location = $request->input('location');
            $record->address = $request->input('address');
            $record->contact = $request->input('contact');
            $record->email = $request->input('email');
            $record->user_id = Auth::id() ?? null;

            $record->save();

            $msg = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($msg, $record, 200);

        } catch (Exception $ex) {
            Log::error('ContactUs add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getContactUs(Request $request)
    {
        try {

            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search', null);
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = ContactUsModel::query();

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('location', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhere('contact', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $result = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Contact list fetched successfully', $result, 200);

        } catch (Exception $ex) {
            Log::error('ContactUs fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch contact details', ['exception' => $ex->getMessage()], 500);
        }
    }


    public function deleteContactUs(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:contact_us,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = ContactUsModel::find($request->input('id'));
            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $deleted = $record->delete();

            if (! $deleted) {
                return Utility::apiError('Failed to delete record', [], 221);
            }

            return Utility::apiSuccess('Deleted successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('ContactUs delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting contact', ['exception' => $ex->getMessage()], 500);
        }
    }
}
