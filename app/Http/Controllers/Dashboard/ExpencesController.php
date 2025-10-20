<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\ExpansesCompanyDepartmentCustomer;
use App\Models\ExpansesCompanyDetail;
use App\Models\TravelExpanses;
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

    public function addUpdateTravelExpense(Request $request)
    {
        $data = $request->only([
            'legs',
            'accompanying',
            'food',
            'hotel',
            'purchaseEquipment',
            'purchaseHardware',
            'labor',
            'otherExpenses',
            'purpose',
            'totals',
        ]);

        $validator = Validator::make($request->all(), [
            'legs' => 'required|array|min:1',
            'legs.*.from' => 'nullable|string|max:100',
            'legs.*.to' => 'nullable|string|max:100',
            'legs.*.mode' => 'nullable|string|max:50',
            'legs.*.km' => 'nullable|numeric|min:0',
            'legs.*.rate' => 'nullable|numeric|min:0',
            'legs.*.amount' => 'nullable|numeric|min:0',
            'legs.*.otherMode' => 'nullable|string|max:100',

            'accompanying' => 'nullable|array',
            'accompanying.*.name' => 'nullable|string|max:100',

            'food' => 'nullable|array',
            'food.*.name' => 'nullable|string|max:100',
            'food.*.amount' => 'nullable|numeric|min:0',

            'hotel' => 'nullable|array',
            'hotel.*.days' => 'nullable|integer|min:0',
            'hotel.*.amount' => 'nullable|numeric|min:0',

            'purchaseEquipment' => 'nullable|array',
            'purchaseEquipment.*.name' => 'nullable|string|max:100',
            'purchaseEquipment.*.amount' => 'nullable|numeric|min:0',

            'purchaseHardware' => 'nullable|array',
            'purchaseHardware.*.name' => 'nullable|string|max:100',
            'purchaseHardware.*.amount' => 'nullable|numeric|min:0',

            'labor' => 'nullable|array',
            'labor.*.persons' => 'nullable|integer|min:0',
            'labor.*.amount' => 'nullable|numeric|min:0',

            'otherExpenses' => 'nullable|array',
            'otherExpenses.*.name' => 'nullable|string|max:100',
            'otherExpenses.*.amount' => 'nullable|numeric|min:0',

            'purpose' => 'nullable|string|max:255',

            'totals' => 'required|array',
            'totals.travel' => 'nullable|numeric|min:0',
            'totals.food' => 'nullable|numeric|min:0',
            'totals.hotel' => 'nullable|numeric|min:0',
            'totals.equip' => 'nullable|numeric|min:0',
            'totals.hardware' => 'nullable|numeric|min:0',
            'totals.labor' => 'nullable|numeric|min:0',
            'totals.others' => 'nullable|numeric|min:0',
            'totals.grand' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return Utility::apiError('Validation failed', $validator->errors(), 221);
        }

        $travelExpense = TravelExpanses::updateOrCreate(
            ['id' => $request->id ?? null],
            [
                'expanses_company_detail_id'=> 1,
                'purpose' => $data['purpose'],
                'legs' => json_encode($data['legs']),
                'accompanying' => json_encode($data['accompanying']),
                'food' => json_encode($data['food']),
                'hotel' => json_encode($data['hotel']),
                'purchase_equipment' => json_encode($data['purchaseEquipment']),
                'purchase_hardware' => json_encode($data['purchaseHardware']),
                'labor' => json_encode($data['labor']),
                'other_expenses' => json_encode($data['otherExpenses']),
                'totals' => json_encode($data['totals']),
            ]
        );

        // Return populated data after save
        $travelData = [
            'id' => $travelExpense->id,
            'purpose' => $travelExpense->purpose,
            'legs' => $travelExpense->legs,
            'accompanying' => $travelExpense->accompanying,
            'food' => $travelExpense->food,
            'hotel' => $travelExpense->hotel,
            'purchaseEquipment' => $travelExpense->purchase_equipment,
            'purchaseHardware' => $travelExpense->purchase_hardware,
            'labor' => $travelExpense->labor,
            'otherExpenses' => $travelExpense->other_expenses,
            'totals' => $travelExpense->totals,
        ];

        $message = isset($data['id']) ? 'updated successfully' : 'added successfully';

        return Utility::apiSuccess($message, $travelData, 200);
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
