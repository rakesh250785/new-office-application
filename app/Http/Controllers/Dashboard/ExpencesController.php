<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\BillExpansesPayment;
use App\Models\ExpansesCompanyDepartmentCustomer;
use App\Models\ExpansesCompanyDetail;
use App\Models\ExpansesServiceReport;
use App\Models\LinkExpansesOrder;
use App\Models\TravelExpanses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExpencesController extends Controller
{
    public function __construct() {}

    public function addNewExpanses(Request $request)
    {
        try {
            $userId = Auth::id();
            $data = $request->all();

            // Step 1: Create default company details
            $companyDetail = ExpansesCompanyDetail::create([
                'company_id' => $data['company_id'] ?? null,
                'concern_person_name' => $data['concern_person_name'] ?? '',
                'designation' => $data['designation'] ?? '',
                'contact_details' => $data['contact_details'] ?? '',
                'phone_no' => $data['phone_no'] ?? '',
                'email_id' => $data['email_id'] ?? '',
                'user_id' => $userId,
            ]);

            // Step 2: Create default department-customer entry
            ExpansesCompanyDepartmentCustomer::create([
                'expanses_company_detail_id' => $companyDetail->id,
                'department' => $data['department'] ?? '',
                'customer_name' => $data['customer_name'] ?? '',
                'user_id' => $userId,
            ]);

            // Step 3: Create default travel expense entry
            TravelExpanses::create([
                'expanses_company_detail_id' => $companyDetail->id,
                'purpose' => $data['purpose'] ?? '',
                'legs' => $data['legs'] ?? '',
                'accompanying' => $data['accompanying'] ?? '',
                'food' => $data['food'] ?? 0,
                'hotel' => $data['hotel'] ?? 0,
                'purchase_equipment' => $data['purchase_equipment'] ?? 0,
                'purchase_hardware' => $data['purchase_hardware'] ?? 0,
                'labor' => $data['labor'] ?? 0,
                'other_expenses' => $data['other_expenses'] ?? 0,
                'totals' => $data['totals'] ?? 0,
                'user_id' => $userId,
            ]);

            // Step 4: Create or update linked order entry
            LinkExpansesOrder::create(
                [
                    'expanses_company_detail_id' => $companyDetail->id,
                    'purpose_order_no' => $data['purpose_order_no'] ?? '',
                    'purpose' => $data['purpose'] ?? '',
                    'purchase_equipment' => $data['purchase_equipment'] ?? 0,
                    'purchase_hardware' => $data['purchase_hardware'] ?? 0,
                    'labor' => $data['labor'] ?? 0,
                    'totals' => $data['totals'] ?? 0,
                    'user_id' => $userId,
                ]
            );

            // Step 5: Create default bill entry
            BillExpansesPayment::create([
                'expanses_company_detail_id' => $companyDetail->id,
                'advance_payment' => $data['advance_payment'] ?? 0,
                'advance_details' => $data['advance_details'] ?? '',
                'uploaded_file' => json_encode([]),
                'totals' => $data['totals'] ?? 0,
                'user_id' => $userId,
            ]);

            // Step 6: Create default service report entry
            ExpansesServiceReport::create([
                'expanses_company_detail_id' => $companyDetail->id,
                'company_id' => $data['company_id'] ?? null,
                'user_id' => $userId,
                'order_no' => $data['order_no'] ?? '',
                'uploaded_file' => json_encode([]),
                'totals' => $data['totals'] ?? 0,
            ]);

            return Utility::apiSuccess('entry created successfully', [], 200);

        } catch (Exception $ex) {
            Log::error('Error in addNewExpanses: '.$ex->getMessage());

            return Utility::apiError('Error creating new expense record', ['exception' => $ex->getMessage()]);
        }
    }

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
                    'user_id' => Auth::id(),
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
                            'user_id' => Auth::id(),
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
            'id',
            'expanses_company_detail_id',
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
            ['id' => (int) $data['id'] ?? null,
                'expanses_company_detail_id' => (int) $data['expanses_company_detail_id'] ?? null,
            ],
            [
                'expanses_company_detail_id' => $data['expanses_company_detail_id'] ?? null,
                'purpose' => $data['purpose'],
                'legs' => $data['legs'],
                'accompanying' => $data['accompanying'],
                'food' => $data['food'],
                'hotel' => $data['hotel'],
                'purchase_equipment' => $data['purchaseEquipment'],
                'purchase_hardware' => $data['purchaseHardware'],
                'labor' => $data['labor'],
                'other_expenses' => $data['otherExpenses'],
                'totals' => $data['totals'],
                'user_id' => Auth::id(),
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

    public function addUpdateLinkOrder(Request $request)
    {
        $data = $request->only([
            'id',
            'expanses_company_detail_id',
            'purpose_order_no',
            'purchaseEquipment',
            'purchaseHardware',
            'labor',
            'purpose',
            'totals',
        ]);

        $validator = Validator::make($request->all(), [
            'purpose_order_no' => 'sometimes|nullable',
            'purchaseEquipment' => 'nullable|array',
            'purchaseEquipment.*.name' => 'nullable|string|max:100',
            'purchaseEquipment.*.amount' => 'nullable|numeric|min:0',

            'purchaseHardware' => 'nullable|array',
            'purchaseHardware.*.name' => 'nullable|string|max:100',
            'purchaseHardware.*.amount' => 'nullable|numeric|min:0',

            'labor' => 'nullable|array',
            'labor.*.persons' => 'nullable|integer|min:0',
            'labor.*.amount' => 'nullable|numeric|min:0',

            'purpose' => 'nullable|string|max:255',
            'totals' => 'required|array',
            'totals.equip' => 'nullable|numeric|min:0',
            'totals.hardware' => 'nullable|numeric|min:0',
            'totals.labor' => 'nullable|numeric|min:0',
            'totals.grand' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return Utility::apiError('Validation failed', $validator->errors(), 221);
        }

        $linkOrder = LinkExpansesOrder::updateOrCreate(
            ['id' => (int) $data['id'] ?? null,
                'expanses_company_detail_id' => (int) $data['expanses_company_detail_id'] ?? null,
            ],
            [
                'expanses_company_detail_id' => $data['expanses_company_detail_id'] ?? null,
                'purpose_order_no' => $data['purpose_order_no'],
                'purpose' => $data['purpose'],
                'purchase_equipment' => $data['purchaseEquipment'],
                'purchase_hardware' => $data['purchaseHardware'],
                'labor' => $data['labor'],
                'totals' => $data['totals'],
                'user_id' => Auth::id(),
            ]
        );

        // Return populated data after save
        $travelData = [
            'id' => $linkOrder->id,
            'purpose' => $linkOrder->purpose,
            'purpose_order_no' => $linkOrder->purpose_order_no,
            'purchaseEquipment' => $linkOrder->purchase_equipment,
            'purchaseHardware' => $linkOrder->purchase_hardware,
            'labor' => $linkOrder->labor,
            'totals' => $linkOrder->totals,
        ];

        $message = isset($data['id']) ? 'updated successfully' : 'added successfully';

        return Utility::apiSuccess($message, $travelData, 200);
    }

    public function addUpdateBillPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'sometimes|nullable|integer',
                'expanses_company_detail_id' => 'required|integer',
                'advance_payment' => 'nullable',
                'advance_details' => 'nullable',
                'totals' => 'required',
                'totals.grand' => 'nullable|numeric|min:0',
                'file_upload' => 'sometimes',
                'upload_file' => 'sometimes|array',
                'upload_file.*' => 'file|mimes:pdf,jpeg,png,jpg|max:5120',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $id = (int) $request->input('id', 0);

            $rawFileUpload = $request->input('file_upload', '[]');

            if (is_string($rawFileUpload)) {
                $rows = json_decode($rawFileUpload, true) ?: [];
            } elseif (is_array($rawFileUpload)) {
                $rows = $rawFileUpload;
            } else {
                $rows = [];
            }

            $uploadedFiles = collect($request->file('upload_file') ?: $request->file('upload_file.*') ?: [])
                ->filter(fn ($f) => $f instanceof \Illuminate\Http\UploadedFile)
                ->values()
                ->all();

            $normalized = collect($rows)->map(function ($row) use (&$uploadedFiles) {
                $fileIndex = $row['fileIndex'] ?? null;
                $fileUrl = $row['existingFileUrl'] ?? $row['fileUrl'] ?? $row['file_url'] ?? null;
                $fileName = $row['fileName'] ?? $row['file_name'] ?? null;

                if (is_numeric($fileIndex) && isset($uploadedFiles[$fileIndex])) {
                    $file = $uploadedFiles[$fileIndex];
                    $path = $file->store('invoices', 'public');
                    $fileUrl = asset('storage/'.$path);
                    $fileName = $file->getClientOriginalName();
                }

                return [
                    'rowId' => $row['rowId'] ?? (string) Str::uuid(),
                    'name' => $row['name'] ?? null,
                    'amount' => ($row['amount'] === '' ? null : $row['amount']) ?? null,
                    'fileUrl' => $fileUrl,
                    'fileName' => $fileName,
                ];
            })->values()->toArray();

            $payload = [
                'expanses_company_detail_id' => (int) $request->input('expanses_company_detail_id'),
                'advance_payment' => $request->input('advance_payment'),
                'advance_details' => $request->input('advance_details'),
                'uploaded_file' => $normalized,
                'totals' => $request->input('totals'),
                'user_id' => Auth::id(),
            ];

            // Create or update record
            $bill = BillExpansesPayment::updateOrCreate(
                [
                    'id' => $id,
                    'expanses_company_detail_id' => (int) $request->input('expanses_company_detail_id'),
                ],
                $payload
            );

            $message = $id ? 'updated successfully' : 'added successfully';

            $uploaded_file = $bill->uploaded_file;
            if (is_string($uploaded_file)) {
                $uploaded_file = json_decode($uploaded_file, true) ?: [];
            }

            $response = [
                'id' => $bill->id,
                'advance_payment' => $bill->advance_payment,
                'advance_details' => $bill->advance_details,
                'uploaded_file' => $bill->uploaded_file,
                'totals' => $bill->totals,
            ];

            return Utility::apiSuccess($message, $response, $id ? 200 : 201);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error in addUpdateBillPayment', ['exception' => $ex->getMessage()]);
        }
    }

    public function addUpdateServiceReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'sometimes|nullable|integer',
                'expanses_company_detail_id' => 'required|integer',
                'order_no' => 'nullable',
                'totals' => 'required',
                'totals.grand' => 'nullable|numeric|min:0',
                'file_upload' => 'sometimes',
                'upload_file' => 'sometimes|array',
                'upload_file.*' => 'file|mimes:pdf,jpeg,png,jpg|max:5120',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $id = (int) $request->input('id', 0);

            $rawFileUpload = $request->input('file_upload', '[]');

            if (is_string($rawFileUpload)) {
                $rows = json_decode($rawFileUpload, true) ?: [];
            } elseif (is_array($rawFileUpload)) {
                $rows = $rawFileUpload;
            } else {
                $rows = [];
            }

            $uploadedFiles = collect($request->file('upload_file') ?: $request->file('upload_file.*') ?: [])
                ->filter(fn ($f) => $f instanceof \Illuminate\Http\UploadedFile)
                ->values()
                ->all();

            $normalized = collect($rows)->map(function ($row) use (&$uploadedFiles) {
                $fileIndex = $row['fileIndex'] ?? null;
                $fileUrl = $row['existingFileUrl'] ?? $row['fileUrl'] ?? $row['file_url'] ?? null;
                $fileName = $row['fileName'] ?? $row['file_name'] ?? null;

                if (is_numeric($fileIndex) && isset($uploadedFiles[$fileIndex])) {
                    $file = $uploadedFiles[$fileIndex];
                    $path = $file->store('invoices', 'public');
                    $fileUrl = asset('storage/'.$path);
                    $fileName = $file->getClientOriginalName();
                }

                return [
                    'rowId' => $row['rowId'] ?? (string) Str::uuid(),
                    'name' => $row['name'] ?? null,
                    'amount' => ($row['amount'] === '' ? null : $row['amount']) ?? null,
                    'fileUrl' => $fileUrl,
                    'fileName' => $fileName,
                ];
            })->values()->toArray();

            $payload = [
                'expanses_company_detail_id' => (int) $request->input('expanses_company_detail_id'),
                'company_id' => $request->input('company_id'),
                'user_id' => Auth::user()->id,
                'order_no' => $request->input('order_no'),
                'uploaded_file' => $normalized,
                'totals' => $request->input('totals'),
            ];

            // Create or update record
            $serviceReport = ExpansesServiceReport::updateOrCreate(
                [
                    'id' => $id,
                    'expanses_company_detail_id' => (int) $request->input('expanses_company_detail_id'),
                ],
                $payload
            );

            $message = $id ? 'updated successfully' : 'added successfully';

            $uploaded_file = $serviceReport->uploaded_file;
            if (is_string($uploaded_file)) {
                $uploaded_file = json_decode($uploaded_file, true) ?: [];
            }

            $response = [
                'id' => $serviceReport->id,
                'order_no' => $serviceReport->order_no,
                'company_id' => $serviceReport->company_id,
                'uploaded_file' => $serviceReport->uploaded_file,   
                'totals' => $serviceReport->totals,
            ];

            return Utility::apiSuccess($message, $response, $id ? 200 : 201);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error in addUpdateServiceReport', ['exception' => $ex->getMessage()]);
        }
    }

    public function getExpansesDetails(Request $request)
    {
        try {
            $records = ExpansesCompanyDetail::with('departmentCustomers', 'company', 'travelExpanses', 'linkOrder', 'paymentBill', 'serviceReport')
                ->latest()
                ->first();

            return Utility::apiSuccess('Latest expanses details fetched', $records, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error addUpdateExpances', ['exception' => $ex->getMessage()]);
        }
    }
}
