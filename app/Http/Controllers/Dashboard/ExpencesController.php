<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\ExpansesCompanyDepartmentCustomer;
use App\Models\ExpansesCompanyDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ExpencesController extends Controller
{
    public function addUpdateExpances(Request $request)
    {
        $data = $request->only([
            'id',
            'company_id',
            'concern_person_name',
            'designation',
            'contact_details',
            'phone_no',
            'email_id',
            'departmentCustomers',
        ]);
        $validator = Validator::make($data, [
            'id' => 'nullable|integer|exists:expanses_company_details,id',
            'company_id' => 'required|integer',
            'concern_person_name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'contact_details' => 'required|string|max:255',
            'phone_no' => 'required|string|max:20',
            'email_id' => 'required|email|max:255',

            // 'departmentCustomers' => 'nullable|array',
            // 'departmentCustomers.*.id' => 'nullable|integer|exists:expanses_company_department_customers,id',
            // 'departmentCustomers.*.department' => 'nullable|integer',
            // 'departmentCustomers.*.customer_name' => 'required|string|max:255',
        ]);

        // Return validation error
        if ($validator->fails()) {
            return Utility::apiError('Validation failed', $validator->errors(), 221);
        }

        try {
            $companyDetail = ExpansesCompanyDetail::updateOrCreate(
                ['id' => $data['id'] ?? null],
                [
                    'company_id' => $data['company_id'],
                    'concern_person_name' => $data['concern_person_name'],
                    'designation' => $data['designation'],
                    'contact_details' => $data['contact_details'],
                    'phone_no' => $data['phone_no'],
                    'email_id' => $data['email_id'],
                ]
            );

            if (isset($data['departmentCustomers']) && ! empty($data['departmentCustomers'])) {
                $existingIds = [];

                foreach ($data['departmentCustomers'] as $item) {
                    $record = ExpansesCompanyDepartmentCustomer::updateOrCreate(
                        [
                            'id' => $item['id'] ?? null,
                            'expanses_company_detail_id' => $companyDetail->id,
                        ],
                        [
                            'department' => $item['department'] ?? null,
                            'customer_name' => $item['customer_name'] ?? null,
                        ]
                    );

                    $existingIds[] = $record->id;
                }

                // Delete removed records
                ExpansesCompanyDepartmentCustomer::where('expanses_company_detail_id', $companyDetail->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            // Reload relations for clean response
            $companyDetail->refresh()->load('departmentCustomers', 'company');

            $message = isset($data['id']) ? 'updated successfully' : 'added successfully';

            return Utility::apiSuccess($message, $companyDetail, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error addUpdateExpances', ['exception' => $ex->getMessage()]);
        }
    }

    public function getExpansesDetails(Request $request)
    {
        try {
            $records = ExpansesCompanyDetail::with('departmentCustomers', 'company')
                ->latest()
                ->first();

            return Utility::apiSuccess('Latest expanses details fetched', $records, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error addUpdateExpances', ['exception' => $ex->getMessage()]);
        }
    }
}
