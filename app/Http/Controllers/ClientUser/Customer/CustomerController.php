<?php

namespace App\Http\Controllers\ClientUser\Customer;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\States;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Customer;
use Exception, Log;

use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function __construct()
    {
    }
    public function addUpdateCustomer(Request $request)
    {
        try {
            # Extract relevant fields
            $data = $request->only([
                'gst_number',
                'company_name',
                'customer_name',
                'email_id',
                'mobile_no',
                'landline_no',
                'address',
                'customer_id',
                'owner_id',
                'country_id',
                'state_id',
                'classification_id',
                'other_state',
                'pin_code',
                'city',
                'state_dd'
            ]);

            # Basic validation rules
            $rules = [
                'company_name' => 'required|string|max:255',
                'customer_name' => 'required|string|max:255',
                'email_id' => 'required|email|max:255',
                'mobile_no' => 'required|digits_between:10,11',
                'landline_no' => 'required|digits_between:6,11',

                'address' => 'required|string|max:1000',
                'customer_id' => 'nullable|integer|exists:customers,id',
                'owner_id' => 'required|integer|exists:owners,id',
                'country_id' => 'required|integer',
                'classification_id' => 'required|integer',
                'state_id' => 'nullable|integer',
                'city' => 'required|string|max:255',
                'pin_code' => 'required|string|max:10',
                'gst_number' => 'nullable|string|max:20',
                'other_state' => 'required|string|max:255',
            ];

            # Option validation rule
            if ($data['state_dd'] == 'India') {
                $rules['gst_number'] = 'required|string|max:20';
                $rules['state_id'] = 'required|integer';
                $rules['other_state'] = 'nullable|string|max:255';
            }

            # Validation error
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Create or update customer
            $customer = Customer::updateOrCreate(
                [
                    'id' => $data['customer_id'] ?? null,
                    'email_id' => $data['email_id'],
                ],
                [
                    'customer_name' => $data['customer_name'] ?? null,
                    'last_name' => null,
                    'company_name' => $data['company_name'] ?? null,
                    'address' => $data['address'] ?? null,
                    'country_id' => $data['country_id'] ?? null,
                    'state_id' => $data['state_id'] ?? null,
                    'city' => $data['city'] ?? null,
                    'pin_code' => $data['pin_code'] ?? null,
                    'gst_number' => $data['gst_number'] ?? null,
                    'mobile_no' => $data['mobile_no'] ?? null,
                    'landline_no' => $data['landline_no'] ?? null,
                    'classification_id' => $data['classification_id'] ?? null,
                    'owner_id' => $data['owner_id'] ?? null,
                    'other_state' => $data['other_state'] ?? null,
                    'email_id' => $data['email_id'] ?? null,
                    'branch_id' => Auth::user()['branch_id'],
                    'user_id' => Auth::user()['id'],
                ]
            );

            # Return if empty
            if (!$customer) {
                return Utility::apiError('Failed to save customer.', [], 221);
            }

            # Return response
            return Utility::apiSuccess('created successfully.', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to customer.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCustomer(Request $request)
    {
        try {
            # Extract filters and pagination
            $data = $request->only([
                'page',
                'per_page',
                'start_date',
                'end_date',
                'download',
                'branch_list',
                'owner_list',
                'search',
            ]);

            # Base query with relationships
            $query = Customer::with([
                'owner:id,name',
                'branch:id,name',
                'state:id,name',
                'classification:id,name',
                'country:id,name'
            ])->whereNull('deleted_at');

            # Filter by owner ID
            if (!empty($data['branch_list'])) {
                $query->whereIn('branch_id', $data['branch_list']);
            }

            # Filter by owner ID
            if (!empty($data['owner_list'])) {
                $query->whereIn('owner_id', $data['owner_list']);
            }

            # Search across multiple columns + relationships
            if (!empty($data['search'])) {
                $search = '%' . $data['search'] . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', $search)
                        ->orWhere('company_name', 'like', $search)
                        ->orWhere('address', 'like', $search)
                        ->orWhere('city', 'like', $search)
                        ->orWhere('pin_code', 'like', $search)
                        ->orWhere('gst_number', 'like', $search)
                        ->orWhere('mobile_no', 'like', $search)
                        ->orWhere('landline_no', 'like', $search)
                        ->orWhere('other_state', 'like', $search)
                        ->orWhereHas('classification', fn($b) => $b->where('name', 'like', $search))
                        ->orWhereHas('country', fn($b) => $b->where('name', 'like', $search))
                        ->orWhereHas('state', fn($b) => $b->where('name', 'like', $search))
                        ->orWhereHas('branch', fn($b) => $b->where('name', 'like', $search))
                        ->orWhereHas('owner', fn($o) => $o->where('name', 'like', $search));
                });
            }

            # Execute query
            $perPage = $data['per_page'] ?? config('constant.per_page', 15);
            $customer = $query->orderByDesc('id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Customer list fetched successfully.', $customer, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to fetch customers.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteCustomer(Request $request)
    {
        try {
            # Extract relevant fields
            $data = $request->only([
                'id',
            ]);

            #  Validation rule
            $validator = Validator::make($data, [
                'id' => 'required'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete user
            $records = Customer::where('id', $data['id'])->delete();

            # Return if fail
            if (!$records) {
                return Utility::apiError('Failed to delete customer', [], 221);
            }

            # Return response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error in  deleteCustomer.', ['exception' => $ex->getMessage()], 500);
        }
    }

}

