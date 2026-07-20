<?php

namespace App\Http\Controllers\SaleInsight\Quotation;

use App\Exports\QuotationExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessQuotation;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\PendingQuotation;
use App\Models\Quotation as QuotationAdd;
use App\Models\QuotationDetail;
use App\Models\QuotationFormat;
use App\Models\QuotationType;
use App\Models\ReasonType;
use App\Models\States;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Log;

class QuotationDetailController extends Controller
{
    public function __construct() {}

    public function addUpdateQuotation(Request $request)
    {
        try {

            // Request specific fields
            $data = $request->only([
                'product_id',
                'product_description',
                'principal_type',
                'payment_term_condition',
                'lead_from',
                'billing_address',
                'billing_city',
                'billing_state_id',
                'billing_mobile',
                'billing_email',
                'billing_landline',
                'billing_pin_code',
                'contact_person',
                'shipping_address',
                'shipping_city',
                'shipping_state_id',
                'shipping_email',
                'company_id',
                'quotation_type_id',
                // 'notification_id',
                'owner_id',
                'date',
                'enq_ref',
                'prepard_by',
                'currency_id',
                'shipping_pin_code',
                'shipping_mobile',
                'shipping_email',
                'shipping_landline',
                'delivery_date_id',
                'product_list',
                'update_status',
                'quotation_id',
                'total_amount',
                'delivery_date_custom',
                'submit_type',
                'state',
                'country',
                'quotation_type',
                'company_details',
                'show_note',
            ]);

            // Validation rule
            $validator = Validator::make($data, [
                'submit_type' => 'required',
                'product_id' => 'nullable|integer|exists:products,id',
                'product_description' => 'sometimes|nullable',
                'principal_type' => 'nullable|string',
                'payment_term_condition' => 'required|string',
                'lead_from' => 'required|string|max:255',
                'billing_address' => 'required|string',
                'billing_city' => 'required|string|max:255',
                'billing_state_id' => 'sometimes|nullable|integer|exists:states,id',
                'billing_mobile' => 'sometimes|nullable|string|max:11',
                'billing_email' => 'sometimes|nullable|email|max:255',
                'billing_landline' => 'sometimes|nullable|string|max:11',
                'billing_pin_code' => 'required|string|max:10',
                'contact_person' => 'required|string|max:255',

                'shipping_address' => 'required|string',
                'shipping_city' => 'required|string|max:255',
                'shipping_state_id' => 'sometimes|nullable|integer|exists:states,id',
                'shipping_pin_code' => 'required|string|max:10',
                'shipping_mobile' => 'required|string|max:11',
                'shipping_email' => 'required|email|max:255',
                'shipping_landline' => 'required|string|max:11',
                'company_id' => 'required|integer|exists:customers,id',
                'quotation_type_id' => 'required|integer|exists:quotation_types,id',
                // 'notification_id' => 'required|integer|exists:notifications_email,id',
                'owner_id' => 'required|integer|exists:owners,id',
                'currency_id' => 'required|integer|exists:currencies,id',
                'delivery_date_id' => 'required|integer|exists:payment_day_advances,id',
                'date' => 'required|date',
                'enq_ref' => 'required|string|max:255',
                'prepard_by' => 'required|string|max:255',
                'update_status' => 'required|boolean',
                'quotation_id' => 'nullable|integer|exists:quotations,id',
                'product_list' => 'required|array|min:1',
                'product_list.*.part_no' => 'required|string',
                'product_list.*.description' => 'required|string|max:1000',
                'product_list.*.hsn_code' => 'required|string|max:50',
                'product_list.*.quantity' => 'required|numeric|min:1',
                'product_list.*.in_stock' => 'nullable|numeric',
                'product_list.*.price' => 'required|numeric|min:0',
                'product_list.*.discount' => 'nullable|numeric|min:0',
                'product_list.*.net_price' => 'required|numeric|min:0',
                'product_list.*.igst' => 'nullable|numeric|min:0',
                'product_list.*.total' => 'required|numeric|min:0',
                'product_list.*.notes' => 'nullable|string|max:1000',
                'product_list.*.product_specification' => 'nullable|string',
                'product_list.*.specification' => 'nullable|string',
                'product_list.*.principal' => 'required_without:product_list.*.principal.type',
                'product_list.*.principal.type' => 'required_without:product_list.*.principal',
                'total_amount' => 'required|numeric|min:0',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            if ($data['submit_type'] == 'quotation_preview') {
                return Utility::apiSuccess('quotation_preview', [], 200);
            }

            // Auth info
            $adminId = Auth::id();
            $branchId = Auth::user()->branch_id;
            $branchName = Branch::findOrFail($branchId)->name;
            $quotationDate = Carbon::now()->format('Y-m-d');

            // Customer and currency info
            $customerInfo = Customer::findOrFail($data['company_id']);
            $currencyInfo = Currency::findOrFail($data['currency_id']);

            // Get unique quotation number
            $existingQuote = QuotationAdd::find($data['quotation_id']);
            $quotationNumber = ! empty($data['quotation_id'])
                ? $existingQuote->unique_quotation_no
                : $this->generateQuotationNumber($branchName, $quotationDate, $branchId);

            // PDF path
            $pdfFilePath = now()->year.'/quotation_'.time().'_'.date('dmy').'.pdf';

            // Prepare quotation data
            $quotationData = [
                'unique_quotation_no' => $quotationNumber,
                'company_id' => $data['company_id'] ?? null,
                'principal_id' => $data['principal_id'] ?? null,
                'billing_address' => $data['billing_address'],
                'billing_city' => $data['billing_city'],
                'billing_mobile' => $data['billing_mobile'],
                'billing_email' => $data['billing_email'],
                'billing_landline' => $data['billing_landline'],
                'billing_pin_code' => $data['billing_pin_code'],
                'billing_state_id' => $data['billing_state_id'],
                'billing_contact_person' => $data['contact_person'],
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_state_id' => $data['shipping_state_id'] ?? null,
                'shipping_pin_code' => $data['shipping_pin_code'] ?? null,
                'shipping_mobile' => $data['shipping_mobile'] ?? null,
                'shipping_email' => $data['shipping_email'] ?? null,
                'shipping_landline' => $data['shipping_landline'] ?? null,
                'product_description' => $data['product_description'] ?? null,
                'delivery_date_id' => $data['delivery_date_id'] ?? null,
                'delivery_date_custom' => $data['delivery_date_custom'] ?? null,
                'lead_from' => $data['lead_from'] ?? null,
                // 'notification_id' => $data['notification_id'] ?? null,
                'owner_id' => $data['owner_id'] ?? null,
                'quotation_type_id' => $data['quotation_type_id'] ?? null,
                'payment_term_condition' => $data['payment_term_condition'] ?? null,
                'date' => $data['date'] ?? null,
                'enq_ref' => $data['enq_ref'] ?? null,
                'prepard_by' => $data['prepard_by'] ?? null,
                'branch_id' => $branchId ?? null,
                'pdf_name' => $pdfFilePath ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'tin_number' => '27700707469',
                'user_id' => $adminId,
                'total_amount' => $data['total_amount'] ?? null,
                'pdf_status' => 'processing',
                'show_note' => $data['show_note'],
            ];

            // Update customer info
            $customerStatus = Customer::where('id', $data['company_id'])->update([
                'address' => $data['billing_address'],
                'city' => $data['billing_city'],
                'pin_code' => $data['billing_pin_code'] ?? null,
                'state_id' => $data['billing_state_id'] ?? null,
                'other_state' => $data['other_state'] ?? null,
                'mobile_no' => $data['billing_mobile'] ?? null,
                'email_id' => $data['billing_email'] ?? null,
                'landline_no' => $data['billing_landline'] ?? null,
            ]);

            // Return if fail
            if (! $customerStatus) {
                return Utility::apiError('Failed to update billing info', [], 221);
            }

            // Add update quoation info
            $quotation = QuotationAdd::updateOrCreate(['id' => $data['quotation_id']], $quotationData);

            // Return if fail
            if (! $quotation) {
                return Utility::apiError('Failed to save / update quotation', [], 221);
            }

            // Add / update reason
            $statusReason = PendingQuotation::updateOrCreate(
                ['quotation_id' => $quotation->id],
                [
                    'quotation_id' => $quotation->id,
                    'unique_quotation_no' => $quotationNumber,
                    'total_amount' => $data['total_amount'] ?? 0,
                    'reason' => 'Open',
                    'status_code' => 'open',
                    'last_updated_at' => Carbon::now(),
                    'user_id' => $adminId,
                    'follow_up_date' => Carbon::now(),
                    'branch_id' => $branchId ?? null,
                    'reason_status_id' => 3,
                ]
            );

            // Return if fail
            if (! $statusReason) {
                return Utility::apiError('Failed to save or update pending reason', [], 221);
            }

            // Initialize variable
            $quotationId = $quotation->id;
            $grandTotal = 0;
            $subUnitTotal = 0;
            $subNetTotal = 0;
            $totalIgstTotal = 0;
            $productList = [];

            // Product calculation
            if (! empty($data['product_list']) && is_array($data['product_list'])) {
                if (! empty($data['quotation_id'])) {
                    QuotationDetail::where('quotation_id', $quotationId)->delete();
                }

                foreach ($data['product_list'] as $item) {
                    $price = $item['price'] ?? 0;
                    $quantity = $item['quantity'] ?? 0;
                    $discount = $item['discount'] ?? 0;
                    $igst = $item['igst'] ?? 0;

                    $baseAmount = $price * $quantity;
                    $discountAmount = ($baseAmount * $discount) / 100;
                    $afterDiscount = $baseAmount - $discountAmount;
                    $gstAmount = ($afterDiscount * $igst) / 100;
                    $totalAmount = $afterDiscount + $gstAmount;

                    $grandTotal += $totalAmount;
                    $subUnitTotal += $price;
                    $subNetTotal += $afterDiscount;
                    $totalIgstTotal += $gstAmount;

                    $productList[] = [
                        'quotation_id' => $quotationId,
                        'unique_quotation_no' => $quotationNumber,
                        'product_id' => $item['product_id'] ?? 0,
                        'principal_id' => $item['principal_id'] ?? null,
                        'part_no' => $item['part_no'] ?? '',

                        'description' => $item['description'] ?? '', // Editable
                        'principal' => $item['principal']['type'] ?? $item['principal'] ?? null, // Editable
                        'heading' => $item['heading'] ?? '', // Editable
                        'specification' => $item['specification'] ?? '', // Editable

                        'hsn_code' => $item['hsn_code'] ?? '',
                        'quantity' => $quantity, // Editable
                        'in_stock' => $item['in_stock'] ?? 0,
                        'price' => $price, // Editable
                        'discount' => $discount, // Editable
                        'net_price' => $afterDiscount,
                        'igst' => $igst,
                        'total' => $totalAmount,
                        'notes' => $item['notes'] ?? null, // Editable
                        'product_specification' => $item['product_specification'] ?? null, // Editable
                        'delivery_date_id' => $item['delivery_date_id'] ?? 0,
                        'deleted_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'user_id' => $adminId,
                        'branch_id' => $branchId,
                    ];
                }

                // Insert product
                $inserted = QuotationDetail::insert($productList);
                if (! $inserted) {
                    return Utility::apiError('Failed to insert quotation product details.', [], 221);
                }
            }

            // Get pdf info
            $states = States::where('id', $data['billing_state_id'])->first() ?? null;
            $branchAddress = QuotationFormat::where('branch_id', $branchId)->whereNull('deleted_at')->value('branch_address');
            $quotationType = QuotationType::where('id', $data['quotation_type_id'])->first();

            $pdfRec = [
                'term_conditon_bg_img' => url('appLogo/bannerImg2.png'),
                'pdf_name' => $pdfFilePath,
                'old_pdf_name' => $existingQuote?->pdf_name,
                'prepared_by' => $data['prepard_by'],
                'quotationInfo' => [
                    'id' => $quotationId,
                    'unique_quotation_no' => $quotationNumber,
                ],
                'company' => [
                    'name' => 'Chromatography World',
                    'address_line1' => '217, 2nd Floor, Champaklal Industrial Estate, Sion East, Mumbai - 400022. India',
                    'contact' => '+91 - 022 - 43159100',
                    'email' => 'sales@chromatographyworld.com, speed@chromatographyworld.com, gm-support@chromatographyworld.com',
                    'gstin' => '27AAGFC1217K1ZM',
                    'udyam_no' => 'UDYAM-MH-19-0078510',
                    'bank' => 'Kotak Mahindra Bank',
                    'ifsc' => 'KKBK0000644',
                    'account' => '4611234274',
                    'web' => 'www.chromatographyworld.com',
                    'branch_name' => 'Matunga ',
                    'logo' => url('appLogo/logo.png'),
                ],
                'quotation' => [
                    'no' => $quotationNumber,
                    'date' => $quotationDate,
                    'ref' => $data['enq_ref'],
                    'quotation_type' => $quotationType?->type,
                ],
                'shipping' => [
                    'company' => $customerInfo->company_name,
                    'address' => $data['billing_address'],
                    'email' => $data['billing_email'],
                    'landline' => $data['billing_landline'],
                    'mobile' => $data['billing_mobile'],
                    'gstn' => $customerInfo->gst_number,
                    'city' => $data['billing_city'],
                    'pincode' => $data['billing_pin_code'],
                    'state' => $states->name ?? $customerInfo->other_state ?? null,
                    'contact_person' => $data['contact_person'],
                    'country' => $data['company_details']['country']['name'] ?? null,
                ],
                'items' => $data['product_list'],
                'branch_address' => $branchAddress,
                'currency' => $currencyInfo,
                'totals' => [
                    'sub_unit_total' => $subUnitTotal,
                    'sub_net_total' => $subNetTotal,
                    'total_igst_total' => $totalIgstTotal,
                    'grand_total' => $grandTotal,
                    'in_words' => Utility::numberToWords($grandTotal, $currencyInfo->name),
                ],
                'terms' => $data['payment_term_condition'],
                'product_description' => $data['product_description'],
                'show_note' => $data['show_note'],
            ];

            // Dispatch for pdf
            ProcessQuotation::dispatch($pdfRec)
            // ->onQueue('quotation_pdf')
                ->delay(0);

            // Return response
            return Utility::apiSuccess(! empty($data['quotation_id']) ? 'updated successfully.' : 'added successfully.', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Quotation add / update failed', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getQuotation(Request $request)
    {
        try {

            ini_set('max_execution_time', 60); // 1 minute
            $data = $request->only([
                'per_page',
                'branch_list',
                'owner_list',
                'currency_list',
                'principal_list',
                'status_list',
                'search',
                'start_date',
                'end_date',
                'download',
            ]);

            $perPage = $data['per_page'] ?? config('constant.per_page', 15);

            if (! empty($data['download'])) {
                $columns = [
                    'branch_name' => 'Branch',
                    'date' => 'Date',
                    'unique_quotation_no' => 'Quotation No',
                    'company' => 'Company Name',
                    'customer_name' => 'Customer Name',
                    'owner' => 'Owner Name',
                    'mobile' => 'Contact Number',
                    'landline' => 'Landline',
                    'email' => 'Contact Email',
                    'part_no' => 'Part No.',
                    'description' => 'Description',
                    'principal' => 'Principal',
                    'price' => 'Price',
                    'quantity' => 'Quantity',
                    'discount' => 'Discount',
                    'net_price' => 'Net Price',
                    'total_amount' => 'Totalo Amount',
                    'notes' => 'Delivery Status',
                    'lead_from' => 'Lead From',
                    'is_order_pending' => 'Status',
                    'reason' => 'Reason',
                ];

                $filename = 'quotation_report_'.now()->format('Ymd_His').'.xlsx';

                (new QuotationExport($data, $columns))->queue("exports/{$filename}", 'public');

                $fileUrl = url("storage/exports/{$filename}");

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => $fileUrl,
                ]);
            }

            $query = QuotationAdd::with([
                'quotationDetails',
                'companyDetails:id,company_name,customer_name,email_id,gst_number,state_id,country_id',
                'branchDetails:id,name',
                'currencyDetails:id,code',
                'ownerDetails:id,name',
                'quotationDetails.uom:id,uom',
                'pendingQuotationDetails:unique_quotation_no,quotation_id,reason,status_code,follow_up_date,total_amount,reason_status_id,last_updated_at',
            ])
                ->whereNull('deleted_at')
                ->when(! empty($data['branch_list']), fn ($q) => $q->where('branch_id', $data['branch_list']))
                ->when(! empty($data['owner_list']), fn ($q) => $q->where('owner_id', $data['owner_list']))
                ->when(! empty($data['currency_list']), fn ($q) => $q->where('currency_id', $data['currency_list']))
                // ->when(! empty($data['status_list']), fn ($q) => $q->where('is_order_pending', (int) $data['status_list']))

                ->when(! empty($data['status_list']), fn ($q) => $q->whereHas('pendingQuotationDetails', fn ($d) => $d->where('reason_status_id', $data['status_list'])))

                ->when(! empty($data['principal_list']), fn ($q) => $q->whereHas('quotationDetails', fn ($d) => $d->where('principal_id', $data['principal_list'])))
                ->when(! empty($data['start_date']) && ! empty($data['end_date']), function ($q) use ($data) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($data['start_date'])->startOfDay(),
                        Carbon::parse($data['end_date'])->endOfDay(),
                    ]);
                })
                ->when(! empty($data['search']), function ($q) use ($data) {
                    $term = $data['search'];
                    $q->where(function ($sub) use ($term) {
                        $sub->where('unique_quotation_no', 'like', "%{$term}%")
                            ->orWhere('lead_from', 'like', "%{$term}%")
                            ->orWhere('total_amount', 'like', "%{$term}%")
                            ->orWhereHas('ownerDetails', fn ($o) => $o->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('currencyDetails', fn ($c) => $c->where('code', 'like', "%{$term}%"))
                            ->orWhereHas('companyDetails', fn ($c) => $c->where('company_name', 'like', "%{$term}%"))
                            ->orWhereHas('companyDetails', fn ($c) => $c->where('customer_name', 'like', "%{$term}%"))
                            ->orWhereHas('quotationDetails', function ($d) use ($term) {
                                $d->where('part_no', 'like', "%{$term}%")
                                    ->orWhereHas('principal', fn ($p) => $p->where('type', 'like', "%{$term}%"));
                            });
                    });
                })
                ->orderByDesc('id');

            if (
                Utility::checkViewPermission('quotation_detail') ||
                Utility::checkBranchesViewPermission('quotation_detail')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('quotation_detail')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('quotation_detail')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }
                });
            }

            $quotationData = $query->paginate($perPage);

            return Utility::apiSuccess('list_quotation', $quotationData, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed getQuotation server error', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteQuotation(Request $request)
    {
        try {
            // Get requested fields
            $data = $request->only(['id']);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:quotations,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            // Delete quotation
            $status = QuotationAdd::where('id', $data['id'])->delete();

            // Return if fail
            if (! $status) {
                return Utility::apiError('Fail to delete quotation', [], 221);
            }

            // Return response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function generateQuotationNumber($branchName, $quotationDate, $type = '')
    {
        try {
            // Branch code (first 3 letters)
            $branchCode = substr($branchName, 0, 3);

            // Dates
            $formattedDate = Carbon::parse($quotationDate)->format('Y-m-d');
            $formattedDateForQuote = Carbon::parse($quotationDate)->format('Ymd');
            $branchId = Auth::user()->branch_id;

            // Get last quote number created on the same day
            $lastQuote = QuotationAdd::whereNull('deleted_at')
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $formattedDate)
                ->orderByDesc('id')
                ->first();

            // Determine next sequence number
            if ($lastQuote && isset($lastQuote->unique_quotation_no)) {
                $segments = explode('/', $lastQuote->unique_quotation_no);
                $lastNumber = (int) ($segments[2] ?? 0);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            // Final quotation number
            return "{$branchCode}/{$formattedDateForQuote}/{$nextNumber}";

        } catch (Exception $ex) {
            Log::error('Failed to generate quotation number: '.$ex->getMessage());

            return null;
        }
    }

    public function updateQuotationStatus(Request $request)
    {
        try {

            // Request specific fields
            $data = $request->only([
                'current_follow_date',
                'reason',
                'reason_status_id',
                'quotation_id',
                'unique_quotation_no',
            ]);

            // Validation rule
            $validator = Validator::make($data, [
                'current_follow_date' => 'required',
                'reason' => 'required',
                'reason_status_id' => 'required|numeric',
                'quotation_id' => 'required|numeric',
                'unique_quotation_no' => 'required',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            // Update quotation reason
            $exitingReasonType = ReasonType::select('id', 'code')->where('id', $data['reason_status_id'])->first();
            $pending = PendingQuotation::where('quotation_id', $data['quotation_id'])
                ->where('unique_quotation_no', $data['unique_quotation_no'])
                ->first();

            if ($pending) {
                // replicate your CONCAT behaviour in PHP (safer vs raw DB concat)
                $pending->reason = $pending->reason
                    ? $pending->reason.', '.$data['reason']
                    : $data['reason'];

                $pending->last_updated_at = Carbon::parse($data['current_follow_date'])->format('Y-m-d H:i:s');
                $pending->reason_status_id = $data['reason_status_id'];
                $pending->status_code = $exitingReasonType['code'];

                $pending->save();
            }

            // Return if fail
            if (! $pending) {
                return Utility::apiError('Failed to update quotation status or reason', [], 500);
            }

            // Return response
            return Utility::apiSuccess('status and reason updated successfully.');
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Fail at statusQuotationChange server error', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function quotationPdfStatus(Request $request)
    {
        try {
            $data = $request->only([
                'quotation_id',
                'unique_quotation_no',
            ]);

            $validator = Validator::make($data, [
                'quotation_id' => 'required',
                'unique_quotation_no' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            $q = QuotationAdd::where('id', $data['quotation_id'] ?? null)
                ->where('unique_quotation_no', $data['unique_quotation_no'] ?? null)
                ->first();

            if ($q?->pdf_status == 'ready' && $q?->pdf_name) {
                return Utility::apiSuccess('Quotation pdf status', [
                    'status' => 'ready',
                    'url' => asset('storage/'.$q->pdf_name),
                ], 200);

            } elseif ($q?->pdf_status == 'processing') {
                return Utility::apiSuccess('Quotation pdf status', [
                    'status' => 'processing',
                    'message' => 'PDF is being Processing',
                ], 200);
            } else {
                return Utility::apiSuccess('Quotation pdf status', [
                    'status' => 'failed',
                    'message' => 'PDF generation failed',
                ], 200);
            }

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Fail to check the status server error', ['exception' => $ex->getMessage()], 500);
        }
    }
}
