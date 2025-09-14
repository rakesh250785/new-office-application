<?php

namespace App\Http\Controllers\SaleInsight\Quotation;

use App\Models\ReasonType;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EntityCreated;
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

            # Request specific fields
            $data = $request->only([
                "product_id",
                "product_description",
                "principal_type",
                "payment_term_condition",
                "lead_from",
                "billing_address",
                "billing_city",
                "billing_state_id",
                "billing_mobile",
                "billing_email",
                "billing_landline",
                "billing_pin_code",
                "contact_person",
                "shipping_address",
                "shipping_city",
                "shipping_state_id",
                "shipping_email",
                "company_id",
                "quotation_type_id",
                "notification_id",
                "owner_id",
                "date",
                "enq_ref",
                "prepard_by",
                "currency_id",
                "shipping_pin_code",
                "shipping_mobile",
                "shipping_email",
                "shipping_landline",
                "delivery_date_id",
                "product_list",
                "update_status",
                "quotation_id",
                "total_amount",
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'product_id' => 'nullable|integer|exists:products,id',
                'product_description' => 'required|string',
                'principal_type' => 'nullable|string',
                'payment_term_condition' => 'required|string',
                'lead_from' => 'required|string|max:255',
                'billing_address' => 'required|string',
                'billing_city' => 'required|string|max:255',
                'billing_state_id' => 'required|integer|exists:states,id',
                'billing_mobile' => 'required|string|max:15',
                'billing_email' => 'required|email|max:255',
                'billing_landline' => 'required|string|max:15',
                'billing_pin_code' => 'required|string|max:10',
                'contact_person' => 'required|string|max:255',
                'shipping_address' => 'required|string',
                'shipping_city' => 'required|string|max:255',
                'shipping_state_id' => 'required|integer|exists:states,id',
                'shipping_pin_code' => 'required|string|max:10',
                'shipping_mobile' => 'required|string|max:15',
                'shipping_email' => 'required|email|max:255',
                'shipping_landline' => 'required|string|max:15',
                'company_id' => 'required|integer|exists:customers,id',
                'quotation_type_id' => 'required|integer|exists:quotation_types,id',
                'notification_id' => 'required|integer|exists:notifications_email,id',
                'owner_id' => 'required|integer|exists:owners,id',
                'currency_id' => 'required|integer|exists:currencies,id',
                'delivery_date_id' => 'required|integer|exists:payment_day_advances,id',
                'date' => 'required|date|after_or_equal:today',
                'enq_ref' => 'required|string|max:255',
                'prepard_by' => 'required|string|max:255',
                'update_status' => 'required|boolean',
                'quotation_id' => 'nullable|integer|exists:quotations,id',
                'product_list' => 'required|array|min:1',
                'product_list.*.part_no' => 'required|string|max:255',
                'product_list.*.description' => 'required|string|max:1000',
                'product_list.*.hsn_code' => 'required|string|max:50',
                'product_list.*.quantity' => 'required|numeric|min:1',
                'product_list.*.in_stock' => 'nullable|numeric|max:255',
                'product_list.*.price' => 'required|numeric|min:0',
                'product_list.*.discount' => 'nullable|numeric|min:0|max:100',
                'product_list.*.net_price' => 'required|numeric|min:0',
                'product_list.*.igst' => 'nullable|numeric|min:0',
                'product_list.*.total' => 'required|numeric|min:0',
                'product_list.*.notes' => 'nullable|string|max:1000',
                'product_list.*.product_specification' => 'nullable|string',
                'total_amount' => 'required|numeric|min:0',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Auth info
            $adminId = Auth::id();
            $branchId = Auth::user()->branch_id;
            $branchName = Branch::findOrFail($branchId)->name;
            $quotationDate = Carbon::now()->format('Y-m-d 00:00:00');

            # Customer and currency info
            $customerInfo = Customer::findOrFail($data['company_id']);
            $currencyInfo = Currency::findOrFail($data['currency_id']);

            # Get unique quotation number
            $quotationNumber = !empty($data['quotation_id'])
                ? Quotation::findOrFail($data['quotation_id'])->unique_quotation_no
                : $this->generateQuotationNumber($branchName, $quotationDate, $branchId);

            # PDF path
            $pdfFilePath = 'quotation_' . time() . '_' . date('dmy') . '.pdf';

            # Prepare quotation data
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
                'lead_from' => $data['lead_from'] ?? null,
                'notification_id' => $data['notification_id'] ?? null,
                'owner_id' => $data['owner_id'] ?? null,
                'quotation_type_id' => $data['quotation_type_id'] ?? null,
                'payment_term_condition' => $data['payment_term_condition'] ?? null,
                'date' => $quotationDate ?? null,
                'enq_ref' => $data['enq_ref'] ?? null,
                'prepard_by' => $data['prepard_by'] ?? null,
                'branch_id' => $branchId ?? null,
                'pdf_name' => $pdfFilePath ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'tin_number' => '27700707469',
                'user_id' => $adminId,
                'total_amount' => $data['total_amount'] ?? null,
            ];

            # Update customer info
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

            # Return if fail
            if (!$customerStatus) {
                return Utility::apiError('Failed to update billing info', [], 221);
            }

            # Add update quoation info
            $quotation = Quotation::updateOrCreate(['id' => $data['quotation_id']], $quotationData);

            # Return if fail
            if (!$quotation) {
                return Utility::apiError('Failed to save / update quotation', [], 221);
            }

            # Add / update reason
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
                    'reason_status_id' => 3
                ]
            );

            # Return if fail
            if (!$statusReason) {
                return Utility::apiError('Failed to save or update pending reason', [], 221);
            }

            # Initialize variable
            $quotationId = $quotation->id;
            $grandTotal = 0;
            $calculations = [];
            $productList = [];

            # Product calculation
            if (!empty($data['product_list']) && is_array($data['product_list'])) {
                if (!empty($data['quotation_id'])) {
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

                    $calculations[] = [
                        'base_amount' => $baseAmount,
                        'discount_amount' => $discountAmount,
                        'net_price' => $afterDiscount,
                        'gst_amount' => $gstAmount,
                        'total' => $totalAmount
                    ];

                    $grandTotal += $totalAmount;

                    $productList[] = [
                        'quotation_id' => $quotationId,
                        'unique_quotation_no' => $quotationNumber,
                        'product_id' => $item['product_id'] ?? 0,
                        'principal_id' => $item['principal_id'] ?? null,
                        'part_no' => $item['part_no'] ?? '',
                        'description' => $item['description'] ?? '',
                        'hsn_code' => $item['hsn_code'] ?? '',
                        'quantity' => $quantity,
                        'in_stock' => $item['in_stock'] ?? 0,
                        'price' => $price,
                        'discount' => $discount,
                        'net_price' => $afterDiscount,
                        'igst' => $igst,
                        'total' => $totalAmount,
                        'notes' => $item['notes'] ?? null,
                        'product_specification' => $item['product_specification'] ?? null,
                        'delivery_date_id' => $item['delivery_date_id'] ?? 0,
                        'deleted_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                # Insert product
                $inserted = QuotationDetail::insert($productList);
                if (!$inserted) {
                    return Utility::apiError('Failed to insert quotation product details.', [], 221);
                }
            }

            # Get pdf info
            $states = $customerInfo->state_id ? States::where('id', $customerInfo->state_id)->first() : null;
            $branchAddress = QuotationFormat::where('branch_id', $branchId)->whereNull('deleted_at')->value('billing_address');

            # Prepare Pdf data
            $responsePayload = array_merge($data, [
                'product_list' => $productList,
                'quotation_info' => [
                    'state_id' => $states[$customerInfo->state_id] ?? null,
                    'shiping_state' => $states[$customerInfo->state_id] ?? null,
                    'extra_notes' => $customerInfo->extra_notes,
                    'gst' => $customerInfo->gst,
                ],
                'customer_info' => $customerInfo,
                'tax_text' => 0,
                'preparing_by' => $data['prepard_by'] ?? null,
                'branch_address' => $branchAddress,
                'currency' => $currencyInfo,
                'file_path' => $pdfFilePath,
                'email' => Auth::user()->email,
                'cc_email' => Auth::user()->cc_email,
                'multiProdCal' => [
                    'calculations' => $calculations,
                    'grand_total' => $grandTotal
                ],
                'totalcalc' => $grandTotal,
            ]);

            # Dispatch for pdf
            // dispatch(new ProcessQuotation($responsePayload));

            # Return response
            return Utility::apiSuccess(!empty($data['quotation_id']) ? 'updated successfully.' : 'added successfully.', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Quotation add / update failed', ['exception' => $ex->getMessage()], 500);
        }
    }


    public function getQuotation(Request $request)
    {
        try {
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

            $query = Quotation::select([
                'id',
                'unique_quotation_no',
                'lead_from',
                'branch_id',
                'owner_id',
                'currency_id',
                'company_id',
                'total_amount',
                'created_at',
                'billing_state_id',
                'billing_address',
                'billing_city',
                'billing_mobile',
                'billing_email',
                'billing_landline',
                'billing_pin_code',
                'billing_contact_person',
                'shipping_address',
                'shipping_city',
                'shipping_pin_code',
                'shipping_mobile',
                'shipping_email',
                'shipping_state_id',
                'shipping_landline',
                'product_description',
                'notification_id',
                'quotation_type_id',
                'payment_term_condition',
                'date',
                'prepard_by',
                'pdf_name',
                'enq_ref',
                'delivery_date_id',
                'is_order_pending',
            ])
                ->with([
                    'quotationDetails',
                    'quotationDetails.principal:id,type',
                    'companyDetails:id,company_name,email_id',
                    'branchDetails:id,name',
                    'currencyDetails:id,code',
                    'ownerDetails:id,name',
                    'pendingQuotationDetails:unique_quotation_no,quotation_id,reason,status_code,follow_up_date,total_amount,reason_status_id,last_updated_at',
                ])
                ->whereNull('deleted_at')
                ->when(!empty($data['branch_list']), fn($q) => $q->whereIn('branch_id', (array) $data['branch_list']))
                ->when(!empty($data['owner_list']), fn($q) => $q->whereIn('owner_id', (array) $data['owner_list']))
                ->when(!empty($data['currency_list']), fn($q) => $q->whereIn('currency_id', (array) $data['currency_list']))
                ->when(!empty($data['status_list']), fn($q) => $q->whereIn('is_order_pending', (array) $data['status_list']))
                ->when(!empty($data['principal_list']), fn($q) => $q->whereHas('quotationDetails', fn($d) => $d->whereIn('principal_id', (array) $data['principal_list'])))
                ->when(!empty($data['start_date']) && !empty($data['end_date']), function ($q) use ($data) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($data['start_date'])->startOfDay(),
                        Carbon::parse($data['end_date'])->endOfDay()
                    ]);
                })
                ->when(!empty($data['search']), function ($q) use ($data) {
                    $term = $data['search'];
                    $q->where(function ($sub) use ($term) {
                        $sub->where('unique_quotation_no', 'like', "%{$term}%")
                            ->orWhere('lead_from', 'like', "%{$term}%")
                            ->orWhere('total_amount', 'like', "%{$term}%")
                            ->orWhereHas('ownerDetails', fn($o) => $o->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('currencyDetails', fn($c) => $c->where('code', 'like', "%{$term}%"))
                            ->orWhereHas('companyDetails', fn($c) => $c->where('customer_name', 'like', "%{$term}%"))
                            ->orWhereHas('quotationDetails', function ($d) use ($term) {
                                $d->where('part_no', 'like', "%{$term}%")
                                    ->orWhereHas('principal', fn($p) => $p->where('type', 'like', "%{$term}%"));
                            });
                    });
                })
                ->orderByDesc('id');

            $quotationData = $query->paginate($perPage);

            return Utility::apiSuccess('list_quotation', $quotationData, 200);

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

    public function deleteQuotation(Request $request)
    {
        try {
            # Get requested fields
            $data = $request->only(['id']);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:quotations,id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Delete quotation
            $status = Quotation::where('id', $data['id'])->delete();

            # Return if fail
            if (!$status) {
                return Utility::apiError('Fail to delete quotation', [], 221);
            }

            # Return response
            return Utility::apiSuccess('deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function generateQuotationNumber($branchName, $quotationDate, $type = '')
    {
        try {
            # Branch code (first 3 letters)
            $branchCode = substr($branchName, 0, 3);

            # Dates
            $formattedDate = Carbon::parse($quotationDate)->format('Y-m-d');
            $formattedDateForQuote = Carbon::parse($quotationDate)->format('Ymd');
            $branchId = Auth::user()->branch_id;

            # Get last quote number created on the same day
            $lastQuote = Quotation::whereNull('deleted_at')
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $formattedDate)
                ->orderByDesc('id')
                ->first();

            # Determine next sequence number
            if ($lastQuote && isset($lastQuote->unique_quotation_no)) {
                $segments = explode('/', $lastQuote->unique_quotation_no);
                $lastNumber = (int) ($segments[2] ?? 0);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            # Final quotation number
            return "{$branchCode}/{$formattedDateForQuote}/{$nextNumber}";

        } catch (Exception $ex) {
            Log::error("Failed to generate quotation number: " . $ex->getMessage());
            return null;
        }
    }

    public function generateOrderNumber($branchName, $quotationDate, $type = '')
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
            $lastQuote = Quotation::whereNull('deleted_at')->where('branch_id', $branchId)
                ->whereDate('created_at', $formattedDate)
                ->orderByDesc('id')
                ->first();

            # If found generate number
            if ($lastQuote && isset($lastQuote->unique_quotation_no)) {
                $segments = explode('/', $lastQuote->unique_quotation_no);
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
    public function updateQuotationStatus(Request $request)
    {
        try {

            # Request specific fields
            $data = $request->only([
                'current_follow_date',
                'reason',
                'reason_status_id',
                'quotation_id',
                'unique_quotation_no'
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'current_follow_date' => 'required',
                'reason' => 'required',
                'reason_status_id' => 'required|numeric',
                'quotation_id' => 'required|numeric',
                'unique_quotation_no' => 'required',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Update quotation reason
            $exitingReasonType = ReasonType::select('id', 'code')->where('id', $data['reason_status_id'])->first();
            $pending = PendingQuotation::where('quotation_id', $data['quotation_id'])
                ->where('unique_quotation_no', $data['unique_quotation_no'])
                ->first();

            if ($pending) {
                // replicate your CONCAT behaviour in PHP (safer vs raw DB concat)
                $pending->reason = $pending->reason
                    ? $pending->reason . ', ' . $data['reason']
                    : $data['reason'];

                $pending->last_updated_at = Carbon::parse($data['current_follow_date'])->format('Y-m-d H:i:s');
                $pending->reason_status_id = $data['reason_status_id'];
                $pending->status_code = $exitingReasonType['code'];

                $pending->save();
            }

            # Return if fail
            if (!$pending) {
                return Utility::apiError('Failed to update quotation status or reason', [], 500);
            }

            # Return response
            return Utility::apiSuccess('status and reason updated successfully.');
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail at statusQuotationChange server error', ['exception' => $ex->getMessage()], 500);
        }
    }
}