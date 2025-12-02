<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\AddressModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function addUpdateAddress(Request $request)
    {
        try {
            $id = $request->input('id');

            $rules = [
                'id' => 'nullable|integer|exists:address,id',
                'first_name' => 'required|string|max:128',
                'last_name' => 'nullable|string|max:128',
                'company' => 'nullable|string|max:191',
                'mobile' => 'nullable|string|max:50',
                'address1' => 'required|string|max:1000',
                'address2' => 'nullable|string|max:1000',
                'pincode' => 'nullable|string|max:20',
                'country_id' => 'nullable|integer',
                'state_id' => 'nullable|integer',
                'city_id' => 'nullable|integer',
                'is_billing_address' => 'nullable|boolean',
                'is_shipping_address' => 'nullable|boolean',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = $request->filled('id') ? AddressModel::find($id) : new AddressModel;

            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $record->web_user_id = $request->input('web_user_id') ?? null;
            $record->user_id = Auth::id() ?? $request->input('user_id') ?? null;
            $record->first_name = $request->input('first_name');
            $record->last_name = $request->input('last_name');
            $record->company = $request->input('company');
            $record->mobile = $request->input('mobile');
            $record->address1 = $request->input('address1');
            $record->address2 = $request->input('address2');
            $record->pincode = $request->input('pincode');
            $record->country_id = $request->input('country_id');
            $record->state_id = $request->input('state_id');
            $record->city_id = $request->input('city_id');
            $record->is_billing_address = (bool) $request->input('is_billing_address', false);
            $record->is_shipping_address = (bool) $request->input('is_shipping_address', false);

            $record->save();

            $msg = $request->filled('id') ? 'updated successfully' : 'created successfully';

            return Utility::apiSuccess($msg, $record, 200);
        } catch (Exception $ex) {
            Log::error('Address add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getAddress(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = $request->input('search', null);
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $userId = $request->input('user_id', null);
            $isBilling = $request->input('is_billing_address', null);
            $isShipping = $request->input('is_shipping_address', null);

            $query = AddressModel::query();

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('company', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%')
                        ->orWhere('address1', 'like', '%'.$search.'%')
                        ->orWhere('address2', 'like', '%'.$search.'%')
                        ->orWhere('pincode', 'like', '%'.$search.'%');
                });
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            if (! empty($userId)) {
                $query->where('web_user_id', $userId);
            }

            if ($isBilling !== null && $isBilling !== '') {
                $query->where('is_billing_address', (bool) $isBilling);
            }

            if ($isShipping !== null && $isShipping !== '') {
                $query->where('is_shipping_address', (bool) $isShipping);
            }

            $result = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('list fetched successfully', $result, 200);
        } catch (Exception $ex) {
            Log::error('Address fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch address', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteAddress(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:address,id',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = AddressModel::find($request->input('id'));
            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }

            $deleted = $record->delete();

            if (! $deleted) {
                return Utility::apiError('Failed to delete record', [], 221);
            }

            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Address delete error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong while deleting address', ['exception' => $ex->getMessage()], 500);
        }
    }
}
