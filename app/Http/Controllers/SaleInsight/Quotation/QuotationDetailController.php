<?php

namespace App\Http\Controllers\SaleInsight\Quotation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PendingQuotation;
use App\Jobs\ProcessQuotation;
use App\Models\QuotationDetail;
use App\Models\QuotationFormat;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Quotation;
use App\Models\States;
use Illuminate\Http\Request;
use App\Models\Customer;
use Exception, Log;
use App\Helpers\Utility;
use Carbon\Carbon;
use Response;
use View;

class QuotationDetailController extends Controller
{
    public function __construct()
    {
    }

    public function addUpdateQuotation(Request $request)
    {
        try {
            # Extract data from request
            $data = $request->only([
                'quotation_format_type',
                'notification_id',
                'customer_id',
                'owner_id',
                'lead_from',
                'enqury_ref_no',
                'contact_persion',
                'quotation_date',
                'quotation_prepare_by',
                'billing_info',
                'shipping_info',
                'quotation_details',
                'currency_id',
                'shipment_invoice_id',
                'notes',
                'term_condition_notes',
                'show_term_condition_notes',
                'amount',
                'quotation_id'
            ]);

            # Meta
            $adminId = Auth::id();
            $branchId = Auth::user()->branch_id;
            $branch = Branch::findOrFail($branchId);
            $branchName = $branch->name;
            $quotationDate = Carbon::now()->format('Y-m-d 00:00:00');

            # Fetch customer and currency
            $customerInfo = Customer::findOrFail($data['customer_id']);
            $currencyInfo = Currency::findOrFail($data['currency_id']);

            # Generate quotation number and PDF file name (only for add)
            $quotationNumber = $data['quotation_id']
                ? Quotation::findOrFail($data['quotation_id'])->quotation_number
                : $this->generateQuotationNumber($branchName, $quotationDate, $branchId);

            $pdfFilePath = 'quotation_' . time() . '_' . date('dmy') . '.pdf';

            # Prepare quotation data
            $quotationData = [
                'quotation_number' => $quotationNumber,
                'customer_id' => $data['customer_id'],
                'shipping_address' => $data['shipping_info']['shipping_address'] ?? null,
                'shiping_city' => $data['shipping_info']['shiping_city'] ?? null,
                'shipping_state' => $data['shipping_info']['state_id'] ?? null,
                'shiping_pincode' => $data['shipping_info']['shiping_pincode'] ?? null,
                'same_as_billing' => $data['shipping_info']['same_as_billing'] ?? null,
                'shipping_phone' => $data['shipping_info']['shipping_phone'] ?? null,
                'shipping_email' => $data['shipping_info']['shipping_email'] ?? null,
                'landline' => $data['shipping_info']['landline'] ?? null,
                'tin_number' => '27700707469',
                'delivery_period' => 30,
                'shipment_invoice_id' => $data['shipment_invoice_id'] ?? null,
                'show_term_condition_notes' => $data['show_term_condition_notes'] ?? null,
                'extra_notes' => $data['extra_notes'] ?? null,
                'enquery_reference_number' => $data['enquery_reference_number'] ?? null,
                'quotation_prepare_by' => $data['quotation_prepare_by'] ?? null,
                'lead_from' => $data['lead_from'] ?? null,
                'notification_id' => $data['notification_id'] ?? null,
                'owner_id' => $data['owner_id'] ?? null,
                'quotation_format_type' => $data['quotation_format_type'] ?? null,
                'term_condition_notes' => $data['term_condition_notes'] ?? null,
                'tax_branch_id' => 1,
                'reference_date' => $quotationDate,
                'login_id' => $adminId,
                'branch_id' => $branchId,
                'stn_pdf_name' => $pdfFilePath,
                'currency_id' => $data['currency_id'],
                'updated_at' => Carbon::now(),
            ];

            # Update customer billing info
            $billingInfo = $data['billing_info'] ?? [];
            $customerUpdate = [
                'address' => $billingInfo['address'] ?? null,
                'city' => $billingInfo['city'] ?? null,
                'contact_person1' => $billingInfo['contact_person1'] ?? null,
                'pin_code' => $billingInfo['pin_code'] ?? null,
                'state_id' => $billingInfo['state_id'] ?? null,
                'mobile' => $billingInfo['mobile'] ?? null,
                'email' => $billingInfo['email'] ?? null,
                'land_line' => $billingInfo['land_line'] ?? null,
            ];

            # Customer details
            $customerUpdated = Customer::where('id', $data['customer_id'])
                ->when(!Auth::user()->hasPermission('branch_all'), function ($q) use ($branchId) {
                    return $q->where('branch_id', $branchId);
                })->update($customerUpdate);

            if (!$customerUpdated)
                return Utility::apiError('Failed to update customer billing info', [], 422);

            # Insert or update quotation
            if ($data['quotation_id']) {
                Quotation::where('id', $$data['quotation_id'])->update($quotationData);
            } else {
                $quotationData['created_at'] = Carbon::now();
                $quotationId = Quotation::insertGetId($quotationData);
            }

            # Insert reason only for new record
            if (!$quotationId)
                return Utility::apiError('Failed to save quotation', [], 500);

            if (!$quotationId || (!$quotationId && !$quotationData['quotation_number']))
                return Utility::apiError('Quotation creation failed', [], 422);

            if (!$quotationId) {
                $reasonData = [
                    'quotation_id' => $quotationId,
                    'unique_quotation_number' => $quotationNumber,
                    'amount' => $data['amount'] ?? 0,
                    'customer_id' => $data['customer_id'],
                    'reason' => 'Open',
                    'reason_mode' => 0,
                    'branch_id' => $branchId,
                    'user_id' => $adminId,
                    'notification_id' => $data['notification_id'] ?? 0,
                ];
                PendingQuotation::create($reasonData);
            }

            # Replace or insert quotation details
            if ($quotationId && is_array($data['quotation_details'])) {
                QuotationDetail::where('quotation_id', $quotationId)->delete();
                $productList = [];
                foreach ($data['quotation_details'] as $item) {
                    $productList[] = array_merge($item, [
                        'customer_id' => $customerInfo->id,
                        'quotation_id' => $quotationId,
                        'delivery_period' => $item['delivery_period'] ?? 0,
                    ]);
                }
                QuotationDetail::insert($productList);
            }


            # Get quotation details
            $states = States::pluck('name', 'id');
            $quotationDetails = QuotationDetail::where('quotation_id', $quotationId)->whereNull('deleted_at')->orderBy('id')->get();
            $branchAddress = QuotationFormat::where('branch_id', $branchId)
                ->whereNull('deleted_at')->value('billing_address');

            # Build PDF payload
            $responsePayload = array_merge($data, [
                'quotation_details' => $quotationDetails,
                'quotation_info' => [
                    'state_id' => $states[$customerInfo->state_id] ?? null,
                    'shiping_state' => $states[$customerInfo->state_id] ?? null,
                    'extra_notes' => $customerInfo->extra_notes,
                    'gst' => $customerInfo->gst,
                ],
                'customer_info' => $customerInfo,
                'tax_text' => 0,
                'preparing_by' => $data['preparing_by'] ?? null,
                'branch_address' => $branchAddress,
                'currency' => $currencyInfo,
                'file_path' => $pdfFilePath,
                'email' => Auth::user()->email,
                'cc_email' => Auth::user()->cc_email,
                'multiProdCal' => $this->calculateProductTotals($data['quotation_details'] ?? []),
                'totalcalc' => $this->calculateTotal($data['quotation_details'] ?? []),
            ]);

            # Dispatch job
            dispatch(new ProcessQuotation($responsePayload));

            # Return reponse
            return Utility::apiSuccess(
                $quotationId ? 'Quotation updated successfully.' : 'Quotation added successfully.',
                ['quotation_id' => $quotationId]
            );
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Quotation save failed', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getQuotation(Request $request)
    {
        try {
            # Get quotation data with required table
            $query = Quotation::with(['details', 'customer', 'pending'])
                ->where('is_deleted', 0)
                ->when(
                    !Auth::user()->hasPermission('branch_all'),
                    fn($q) =>
                    $q->where('in_branch_id', Auth::user()->branch_id)
                )
                ->when(
                    $request->filled('owner_select'),
                    fn($q) =>
                    $q->where('owner_id', $request->owner_select)
                )
                ->when(
                    $request->filled('branch_select'),
                    fn($q) =>
                    $q->where('in_branch_id', $request->branch_select)
                )
                ->when(
                    $request->filled('currency_select'),
                    fn($q) =>
                    $q->where('st_currency_applied', $request->currency_select)
                )
                ->when(
                    $request->filled('status_select'),
                    fn($q) =>
                    $q->where('is_order_pending', (int) $request->status_select)
                )
                ->when(
                    $request->filled('principal_select'),
                    fn($q) =>
                    $q->whereHas(
                        'details',
                        fn($d) =>
                        $d->where('st_maker', $request->principal_select)
                    )
                )
                ->when($request->filled('date_range'), function ($q) use ($request) {
                    [$from, $to] = explode('|', $request->date_range);
                    $q->whereBetween('dt_date_created', [
                        Carbon::parse($from)->startOfDay(),
                        Carbon::parse($to)->endOfDay()
                    ]);
                })
                ->when($request->filled('search.value'), function ($q) use ($request) {
                    $term = $request->input('search.value');
                    $q->where(function ($sub) use ($term) {
                        $sub->where('in_quot_num', 'like', "%$term%")
                            ->orWhere('fl_nego_amt', 'like', "%$term%")
                            ->orWhere('lead_from', 'like', "%$term%")
                            ->orWhereHas(
                                'customer',
                                fn($c) =>
                                $c->where('st_com_name', 'like', "%$term%")
                            )
                            ->orWhereHas(
                                'details',
                                fn($d) =>
                                $d->where('st_part_no', 'like', "%$term%")
                            );
                    });
                })
                ->orderByDesc('in_quot_id');

            $paginated = $query->paginate(15);

            $data = $paginated->getCollection()->transform(function ($quotation) {
                return [
                    'quotation_id' => $quotation->in_quot_id,
                    'quotation_no' => $quotation->in_quot_num,
                    'created_date' => optional($quotation->dt_date_created)->format('d-m-Y'),
                    'company_name' => optional($quotation->customer)->st_com_name,
                    'customer_email' => optional($quotation->customer)->st_cust_email,
                    'mobile' => optional($quotation->customer)->st_cust_mobile,
                    'reason' => optional($quotation->pending)->stn_reason,
                    'follow_date' => optional($quotation->pending)?->modify
                        ? date('d-m-Y', strtotime($quotation->pending->modify))
                        : null,
                    'status' => $this->getStatusLabel($quotation->is_order_pending),
                    'pdf_url' => $this->getQuotationPdfUrl($quotation->stn_pdf_name),
                    'products' => $quotation->details->map(fn($d) => [
                        'part_no' => $d->st_part_no,
                        'description' => $d->st_product_desc,
                        'quantity' => $d->in_pro_qty,
                        'unit_price' => $d->fl_pro_unitprice,
                        'net_price' => $d->fl_net_price,
                    ])
                ];
            });

            return response()->json([
                'code' => 200,
                'data' => $data,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ]
            ]);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed getQuotation server error', ['exception' => $ex->getMessage()], 500);
        }
    }

    protected function getQuotationPdfUrl($filename)
    {
        try {
            # Get  pdf file info
            $year = date('Y');
            $lastYear = date('Y', strtotime('-1 year'));

            # Check if exist
            if (File::exists(public_path("/pdf_$year/$filename"))) {
                return URL::to("/pdf_$year/$filename");
            } elseif (File::exists(public_path("/pdf_$lastYear/$filename"))) {
                return URL::to("/pdf_$lastYear/$filename");
            }

            # Return path
            return URL::to("/quotationpdf/$filename");
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed getQuotationPdfUrl server error', ['exception' => $ex->getMessage()], 500);
        }
    }

    protected function getStatusLabel($status)
    {
        try {
            return match ((int) $status) {
                1 => 'Win',
                2 => 'Lost',
                3 => 'Closed',
                default => 'Open',
            };
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed getStatusLabel server error', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteQuotation(Request $request, $id)
    {
        try {
            # Get requested fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:quotation,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 422);
            }

            # Delete quotation
            $status = (Auth::user()->hasPermission('branch_all')) ? Quotation::where('id', $id)->delete() : Quotation::where(['in_quot_id' => $id, 'in_branch_id' => Auth::user()->branch_id])->delete();

            # Return if fail
            if (!$status) {
                return Utility::apiError('Fail to delete quotation', [], 221);
            }

            # Return response
            return Utility::apiError('Quotation deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function generateQuotationNumber($branchName, $quotationDate, $type = '')
    {
        try {
            # Create format
            $branchCode = substr($branchName, 0, 3);
            $formattedDate = Carbon::parse($quotationDate)->format('Y-m-d');
            $formattedDateForQuote = Carbon::parse($quotationDate)->format('Ymd');
            $branchId = Auth::user()->branch_id;

            # Create type
            $flgType = $type !== '' ? "{$type}-" : '';
            $basePrefix = "{$branchCode}/{$formattedDateForQuote}/{$flgType}";

            # Get last quote number created on the same day
            $lastQuote = Quotation::where([
                ['deleted_at', '=', 0],
                ['branch_id', '=', $branchId],
            ])
                ->whereDate('created_at', $formattedDate)
                ->orderByDesc('id')
                ->first();

            # If found generate number
            if ($lastQuote && isset($lastQuote->in_quot_num)) {
                $segments = explode('/', $lastQuote->in_quot_num);
                $lastNumber = (int) str_replace($flgType, '', $segments[2] ?? 0);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            # Return number
            return "{$basePrefix}{$nextNumber}";
        } catch (Exception $ex) {
            Log::error("Failed to generate quotation number: " . $ex->getMessage());
            return null;
        }
    }

    public function calculateProductTotals($quotation_details)
    {
        $grandTotal = 0;
        $calculations = [];

        foreach ($quotation_details as $product) {
            // Base calculation (price × quantity)
            $baseAmount = $product['fl_pro_unitprice'] * $product['in_pro_qty'];

            // Calculate discount
            $discountAmount = ($baseAmount * $product['fl_discount']) / 100;
            $afterDiscount = $baseAmount - $discountAmount;

            // Calculate GST
            $gstAmount = ($afterDiscount * $product['in_igst_rate']) / 100;

            // Calculate final total for this product
            $totalAmount = $afterDiscount + $gstAmount;

            // Store calculations
            $calculations[] = [
                'base_amount' => $baseAmount,
                'discount_amount' => $discountAmount,
                'net_price' => $afterDiscount,
                'gst_amount' => $gstAmount,
                'total' => $totalAmount
            ];

            $grandTotal += $totalAmount;
        }

        return [
            'calculations' => $calculations,
            'grand_total' => $grandTotal
        ];
    }

    public function previewQuatation(Request $request)
    {
        try {
            # Preview quotaion & field validation
            $pdf = \App::make('dompdf.wrapper');
            $sel_prods_details = $request->sel_prods_details;
            if (!empty($sel_prods_details)) {
                $validator = Validator::make($sel_prods_details[0], [
                    'in_cust_id' => 'required',
                ]);
            }
            $msg1 = $validator->getMessageBag()->toArray();
            $quotation_info = $request->quotation_info;
            if (!empty($quotation_info)) {
                $val = [
                    "st_shiping_add" => 'required',
                    "st_shiping_city" => 'required',
                    "st_shiping_state" => 'required',
                    "st_shiping_pincode" => 'required',
                    "st_shipping_email" => 'required',
                    "st_shipping_phone" => 'required',
                    "st_enq_ref_number" => 'required',
                    'shipping_lanline' => 'required',
                    "dt_ref" => 'required',
                    "st_landline" => 'required',
                    'product_search' => 'required',
                    'prod_qty' => 'required',
                ];
                if (isset($quotation_info['in_quot_id']) && !empty($quotation_info['in_quot_id'])) {
                    unset($val['product_search']);
                }
                $validator1 = Validator::make($quotation_info, $val);
            }
            $msg2 = $validator1->getMessageBag()->toArray();
            $customer_info = $request->customer_info;
            if (!empty($customer_info)) {
                $validator2 = Validator::make($customer_info, [
                    "auto_pop_cust_name" => 'required',
                    "st_cust_mobile" => 'required',
                    "auto_pop_state" => 'required',
                    "preparing_by" => 'required',
                    "lead_from" => 'required',
                    'notify_group' => 'required',
                    'select_owner' => 'required',
                    'auto_pop_addr' => 'required',
                    'auto_pop_state' => 'required',
                    'auto_pop_city' => 'required',
                    'auto_pop_pincod' => 'required',
                    'auto_pop_email' => 'required',
                    'auto_pop_landline' => 'required',
                ]);
            }
            $msg3 = $validator2->getMessageBag()->toArray();
            if ($validator->fails() || $validator1->fails() || $validator2->fails()) {
                $msg = $msg1 + $msg2 + $msg3;
                return Response::json(array(
                    'success' => false,
                    'errors' => $msg
                ), 400);
            }
            $indian_all_states = Config::get('constant.indian_all_states');
            if ($customer_info['country_code'] == 'IN') {
                $address = $customer_info['auto_pop_addr'] . ', State ' . $indian_all_states[$customer_info['auto_pop_state']] . ', City ' . $customer_info['auto_pop_city'] . ', Pincode ' . $customer_info['auto_pop_pincod'];
            } else {
                $address = $customer_info['auto_pop_addr'] . ', State ' . $customer_info['auto_pop_state'] . ', City ' . $customer_info['auto_pop_city'] . ', Pincode ' . $customer_info['auto_pop_pincod'];
            }

            $result = [];
            $billing_address = $request->quotation_info;
            $format = $billing_address['bill_add_id'];
            if ($customer_info['country_code'] == 'IN') {
                $update_state = $request->customer_info;
                $update_state['auto_pop_state'] = $indian_all_states[$update_state['auto_pop_state']];

                $quote_update_state = $request->quotation_info;
                $quote_update_state['st_shiping_state'] = $indian_all_states[$quote_update_state['st_shiping_state']];
            } else {
                $update_state = $request->customer_info;
                $quote_update_state = $request->quotation_info;
            }

            if (!empty($customer_info['country_code'])) {
                $country = Config::get('constant.countries');
                $update_state['country'] = $country[$customer_info['country_code']];
            }

            $result['quotation_details'] = $request->sel_prods_details;
            $result['customer_info'] = $update_state;
            $result['quotation_info'] = $quote_update_state;
            $result['format'] = $format;
            $result['BillAddress'] = $this->get_PDF_BillAddress();
            $result['multiProdCal'] = $this->calculateProductTotals($request->sel_prods_details);

            $cur = Config::get('constant.currency');
            $currencyCodes = Config::get('constant.currencyCodes');
            $qt_info = $request->quotation_info;
            $c_format = $qt_info['currency'];
            $result['currency'] = $currencyCodes[$cur[$c_format]];

            // default quotation

            $quotation_type = $request->quotation_info['quotation_type'];
            if ($quotation_type == 'GW Quotation' || $quotation_type == 'Project Quotation') {
                $data['quotation_data'] = View::make("office.quatation.preview_project_quatation", $result)->render();
            } else {
                $data['quotation_data'] = View::make("office.quatation.preview_quatation", $result)->render();
            }
            return json_encode($data);
        } catch (Exception $ex) {
            // Log the full error
            Log::error('Quotation Preview Error: ' . $ex->getMessage());
            Log::error('Error Trace: ' . $ex->getTraceAsString());

            // Return a consistent error response
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'error_details' => $ex->getTraceAsString()
            ], 500);
        }
    }

    public function calculateTotal($quotation_details)
    {
        $total = 0;

        foreach ($quotation_details as $product) {
            $total += $product['in_pro_qty'] * $product['fl_pro_unitprice'];
        }

        return $total;
    }
    public function statusQuotationChange(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only([
                'quotation_status',
                'quotation_reason',
                'quotation_no',
                'quote_id',
                'follow_up_date'
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'quotation_status' => 'required|in:0,1',
                'quotation_no' => 'required|string',
                'quote_id' => 'required|integer',
                'quotation_reason' => 'required|string',
                'follow_up_date' => 'required|date'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 422);
            }

            # Update quotation status
            $quotationQuery = Quotation::where('id', $data['quote_id'])
                ->where('unique_quotation_number', $data['quotation_no']);

            # If has permission
            if (!Auth::user()->hasPermission('branch_all')) {
                $quotationQuery->where('in_branch_id', Auth::user()->branch_id);
            }

            # Update status
            $quotationUpdated = $quotationQuery->update([
                'is_order_pending' => $data['quotation_status']
            ]);

            # Update quotation reason
            $updateReasonStatus = PendingQuotation::where('stn_qtn_ord_no', $data['quotation_no'])->update([
                'stn_reason' => \DB::raw("CONCAT(stn_reason, ', {$data['quotation_reason']}')"),
                'created_at' => Carbon::parse($data['follow_up_date'])->format('Y-m-d H:i:s'),
                'user_id' => Auth::id(),
                'updated_at' => Carbon::now(),
                'deleted_at' => Carbon::now(),
            ]);

            # Return if fail
            if (!$quotationUpdated && !$updateReasonStatus) {
                return Utility::apiError('Failed to update quotation status or reason', [], 500);
            }

            # Return response
            return Utility::apiSuccess('Quotation status and reason updated successfully.');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail at statusQuotationChange server error', ['exception' => $ex->getMessage()], 500);
        }
    }
}