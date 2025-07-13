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
                'id',
                'name',
                'company_name',
                'email',
                'address',
                'country_id',
                'owner_id',
                'city',
                'pincode',
                'customer_classification',
                'state_id',
                'other_state',
                'gst_no',
                'mobile',
                'land_line',
            ]);

            # Basic validation rules
            $rules = [
                'name' => 'required|string|max:255',
                'company_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:500',
                'country_id' => 'required|integer',
                'owner_id' => 'required|integer|exists:owners,id',
                'city' => 'required|string|max:255',
                'pincode' => 'required|string|max:10',
                'customer_classification' => 'required|string|max:100',
                'mobile' => 'nullable|string|max:20',
                'land_line' => 'nullable|string|max:20',
            ];

            # Fetch country info (assuming country ID maps to Countries table)
            $country = Country::find($data['country_id']);

            # No country found
            if (!$country) {
                return Utility::apiError('Invalid country.', [], 221);
            }

            # Country based state validation
            if (strtolower($country['ocde']) === 'in') {
                $rules['state_id'] = 'required|integer|exists:states,id';
                $rules['gst_no'] = 'required|string|max:25';
            } else {
                $rules['other_state'] = 'required|string|max:100';
            }

            # Validation
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Create or update customer
            $customer = Customer::updateOrCreate(
                [
                    'id' => $data['id'] ?? null,
                    'email' => $data['email'],
                    'branch_id' => Auth::user()->branch_id,
                ],
                [
                    'name' => $data['name'] ?? null,
                    'last_name' => null,
                    'company_name' => $data['company_name'] ?? null,
                    'address' => $data['address'] ?? null,
                    'country_id' => $country['id'] ?? null,
                    'state_id' => $data['state_id'] ?? null,
                    'city' => $data['city'] ?? null,
                    'pincode' => $data['pincode'] ?? null,
                    'gst_number' => $data['gst_no'] ?? null,
                    'mobile' => $data['mobile'] ?? null,
                    'land_line' => $data['land_line'] ?? null,
                    'customer_classification' => $data['customer_classification'] ?? null,
                    'owner_id' => $data['owner_id'] ?? null,
                    'other_state' => $data['other_state'] ?? null
                ]
            );

            # Return if empty
            if (!$customer) {
                return Utility::apiError('Failed to save customer.', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Customer saved successfully.', ['customer_id' => $customer->id], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to customer.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCustomer(Request $request)
    {
        try {
            # Extract filters and pagination
            $data = $request->only(['owner_id', 'branch_id', 'search', 'per_page']);

            # Pagination config
            $perPage = isset($data['per_page']) && (int) $data['per_page'] > 0 ? (int) $data['per_page'] : 10;

            # Base query with relationships
            $query = Customer::with(['owner:id,owner_name', 'branch:id,name'])
                ->whereNull('deleted_at');

            # Branch-level permission
            if (!Auth::user()->hasPermission('branch_all')) {
                $query->where('in_branch', Auth::user()->branch_id);
            } elseif (!empty($data['branch_id'])) {
                $query->where('in_branch', $data['branch_id']);
            }

            # Filter by owner ID
            if (!empty($data['owner_id'])) {
                $query->where('owner_id', $data['owner_id']);
            }

            # Search across multiple columns + relationships
            if (!empty($data['search'])) {
                $search = '%' . $data['search'] . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('company_name', 'like', $search)
                        ->orWhere('address', 'like', $search)
                        ->orWhere('city', 'like', $search)
                        ->orWhere('pincode', 'like', $search)
                        ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", [$search])
                        ->orWhereHas('branch', fn($b) => $b->where('name', 'like', $search))
                        ->orWhereHas('owner', fn($o) => $o->where('name', 'like', $search));
                });
            }

            # Execute query
            $response = $query->orderByDesc('in_cust_id')->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Customer list fetched successfully.', $response, 200);
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
            return Utility::apiSuccess('Customer deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error in  deleteCustomer.', ['exception' => $ex->getMessage()], 500);
        }
    }

}

