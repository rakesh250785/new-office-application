<?php

namespace App\Http\Controllers\ClientUser\Customer;

use App\Exports\CustomerExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Log;

class CustomerController extends Controller
{
    public function __construct() {}

    public function addUpdateCustomer(Request $request)
    {
        try {
            // Extract relevant fields
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
                'state_dd',
            ]);

            // Basic validation rules
            $rules = [
                'company_name' => [
                    Rule::requiredIf(! $request->customer_id),
                    'string',
                    'max:255',
                    Rule::unique('customers', 'company_name')
                        ->ignore($request->customer_id),
                ],
                'customer_name' => 'required|string|max:255',
                'email_id' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('customers', 'email_id')
                        ->ignore($request->customer_id),
                ],
                'mobile_no' => 'sometimes|nullable|digits_between:10,11',
                'landline_no' => 'sometimes|nullable|digits_between:6,11',

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

            // Option validation rule
            if ($data['state_dd'] == 'India') {
                $rules['gst_number'] = 'sometimes|nullable|max:20';
                $rules['state_id'] = 'required|integer';
                $rules['other_state'] = 'nullable|string|max:255';
            }

            // Validation error
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Create or update customer
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

            // Return if empty
            if (! $customer) {
                return Utility::apiError('Failed to save customer.', [], 221);
            }

            $message = ! empty($data['customer_id']) ? 'updated successfully.' : 'created successfully.';

            // Return response
            return Utility::apiSuccess($message, [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to customer.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCustomer(Request $request)
    {
        try {
            // Extract incoming inputs
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

            $page = max((int) ($data['page'] ?? 1), 1);
            $perPage = max((int) ($data['per_page'] ?? config('constant.per_page', 15)), 1);
            $search = isset($data['search']) ? trim($data['search']) : '';
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;

            if (! empty($data['download'])) {
                $columns = [
                    'customer_name' => 'Customer Name',
                    'company_name' => 'Company Name',
                    'email_id' => 'Email',
                    'mobile_no' => 'Mobile',
                    'landline_no' => 'Landline',
                    'address' => 'Address',
                    'city' => 'City',
                    'pin_code' => 'Pincode',
                    'gst_number' => 'GST Number',
                    'other_state' => 'Other State',
                    'classification.name' => 'Classification',
                    'owner.name' => 'Owner',
                    'state.name' => 'State',
                    'country.name' => 'Country',
                    'branch.name' => 'Branch',
                    'created_at' => 'Created At',
                ];

                $filename = 'customer_'.now()->format('Ymd_His').'.xlsx';

                // queue the export (uses same pattern as your Owner example)
                (new CustomerExport($data, $columns, Customer::class))
                    ->queue("exports/{$filename}", 'public');

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => url("storage/exports/{$filename}"),
                ]);
            }

            // Build base query with relationships
            $query = Customer::with([
                'owner:id,name',
                'branch:id,name',
                'state:id,name',
                'classification:id,name',
                'country:id,name',
            ])->whereNull('deleted_at');

            // Apply date filters (created_at)
            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // Apply branch filter
            if (! empty($data['branch_list'])) {
                $query->whereIn('branch_id', (array) $data['branch_list']);
            }

            // Apply owner filter
            if (! empty($data['owner_list'])) {
                $query->whereIn('owner_id', (array) $data['owner_list']);
            }

            // Search across fields & relations
            if ($search !== '') {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('customer_name', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('pin_code', 'like', $like)
                        ->orWhere('gst_number', 'like', $like)
                        ->orWhere('mobile_no', 'like', $like)
                        ->orWhere('landline_no', 'like', $like)
                        ->orWhere('other_state', 'like', $like)
                        ->orWhereHas('classification', fn ($b) => $b->where('name', 'like', $like))
                        ->orWhereHas('country', fn ($b) => $b->where('name', 'like', $like))
                        ->orWhereHas('state', fn ($b) => $b->where('name', 'like', $like))
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', $like))
                        ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', $like));
                });
            }

            // Paginate and return
            $customer = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Customer list fetched successfully.', $customer, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed to fetch customers.', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteCustomer(Request $request)
    {
        try {
            // Extract relevant fields
            $data = $request->only([
                'id',
            ]);

            //  Validation rule
            $validator = Validator::make($data, [
                'id' => 'required',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            // Delete user
            $records = Customer::where('id', $data['id'])->delete();

            // Return if fail
            if (! $records) {
                return Utility::apiError('Failed to delete customer', [], 221);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error in  deleteCustomer.', ['exception' => $ex->getMessage()], 500);
        }
    }
}
