<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\PartialOrder;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\QuotationFormat;
use App\Models\States;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ProcessOrder;
use Illuminate\Support\Facades\Auth;
use App\Models\PendingQuotation;
use Illuminate\Http\Request;
use App\Models\QuatationAdd;
use App\Models\OrderDetails;
use App\Models\Customer;
use App\Jobs\CloseOrder;
use Exception, Log;
use App\Models\Courier;
use Carbon\Carbon;
use Response;
use Config;
use View;

class FullOrderController extends Controller
{
    public function __construct()
    {
    }

    public function storeOrder(Request $request)
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
                "unique_quotation_no",
                'customer_order_no',
                'overdues_value',
                'overdue_no'

            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'customer_order_no' => 'required|string',
                'overdues_value' => 'required|string',
                'overdue_no' => 'required|string',
                "unique_quotation_no" => 'required|string',
                'product_id' => 'nullable|integer|exists:products,id',
                'product_description' => 'required|string',
                'principal_type' => 'nullable|string',
                'payment_term_condition' => 'required|string',
                'lead_from' => 'required|string|max:255',
                'billing_address' => 'required|string|max:500',
                'billing_city' => 'required|string|max:255',
                'billing_state_id' => 'required|integer|exists:states,id',
                'billing_mobile' => 'required|string|max:15',
                'billing_email' => 'required|email|max:255',
                'billing_landline' => 'required|string|max:15',
                'billing_pin_code' => 'required|string|max:10',
                'contact_person' => 'required|string|max:255',
                'shipping_address' => 'required|string|max:500',
                'shipping_city' => 'required|string|max:255',
                'shipping_state_id' => 'required|integer|exists:states,id',
                'shipping_pin_code' => 'required|string|max:10',
                'shipping_mobile' => 'required|string|max:15',
                'shipping_email' => 'required|email|max:255',
                'shipping_landline' => 'required|string|max:15',
                'company_id' => 'required|integer|exists:customers,id',
                'quotation_type_id' => 'required|integer|exists:quotation_types,id',
                'notification_id' => 'required|integer|exists:notifications,id',
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

            # Check if quotation already exist
            $checkExistingQuotationInfo = Order::where(['quotation_id' => $data['quotation_id'], 'unique_quotation_no' => $data['unique_quotation_no']])->first();

            if ($checkExistingQuotationInfo) {
                return Utility::apiError('Already order generated against this quotaion number', [], 221);
            }

            # Auth info
            $adminId = Auth::id();
            $branchId = Auth::user()->branch_id;
            $branchName = Branch::findOrFail($branchId)->name;
            $orderDate = Carbon::now()->format('Y-m-d 00:00:00');

            # Customer and currency info
            $customerInfo = Customer::findOrFail($data['company_id']);
            $currencyInfo = Currency::findOrFail($data['currency_id']);

            # Get unique order number
            $orderNumber = $this->generateOrderNumber($branchName, $branchId, $orderDate, );

            # PDF path
            $pdfFilePath = 'order_' . time() . '_' . date('dmy') . '.pdf';

            # Prepare order data
            $orderData = [
                'unique_order_no' => $orderNumber,
                'company_id' => $data['company_id'] ?? null,
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
                'customer_order_no' => $data['customer_order_no'] ?? null,
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
            $order = Order::create($orderData);

            # Return if fail
            if (!$order) {
                return Utility::apiError('Failed to save order', [], 221);
            }

            # Initialize variable
            $orderId = $order->id;
            $grandTotal = 0;
            $calculations = [];
            $productList = [];

            # Sync order details
            if (OrderDetails::where('order_id', $orderId)->exists()) {
                if (OrderDetails::where('order_id', $orderId)->delete() === false) {
                    return Utility::apiError('Failed to delete existing order details', [], 221);
                }
            }

            # Product calculation

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
                    'order_id' => $orderId,
                    'quotation_id' => $data['quotation_id'],
                    'unique_order_no' => $orderNumber,
                    'unique_quotation_no' => $data['unique_quotation_no'],
                    'product_id' => $data['product_id'] ?? 0,
                    'principal_id' => $data['principal_id'] ?? null,
                    'part_no' => $item['part_no'] ?? '',
                    'description' => $item['description'] ?? '',
                    'hsn_code' => $item['hsn_code'] ?? '',
                    'in_stock' => $item['in_stock'] ?? 0,
                    'price' => $price,
                    'discount' => $discount,
                    'net_price' => $afterDiscount,
                    'igst' => $igst,
                    'balance_quantity' => $item['balance_quantity'] ?? 0,
                    'order_type' => 1,
                    'quantity' => $item['quantity'],
                    'total' => $totalAmount,
                    'status' => 0,
                    'partial_order_status' => 0,
                    'notes' => $item['notes'] ?? null,
                    'product_specification' => $item['product_specification'] ?? null,
                    'delivery_date_id' => $item['delivery_date_id'] ?? 0,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }


            # Insert order details
            $orderDetailInsert = OrderDetails::insert($productList);

            # Return if fail
            if (!$orderDetailInsert) {
                return Utility::apiError('Fail to insert updated order details', [], 221);
            }

            # Refresh quotation product details
            $quotationDetailDelete = QuotationDetail::where('quotation_id', $data['quotation_id'])->delete();

            # Return if fail
            if (!$quotationDetailDelete) {
                return Utility::apiError('Fail to delete existing quotation details', [], 221);
            }

            # Insert quotation details
            $insertStatus = QuotationDetail::insert($productList);

            # Return if fail
            if (!$insertStatus) {
                return Utility::apiError('Fail to delete quotation details', [], 221);
            }

            # Update quotation flags
            $quotationFilter = [
                'id' => $data['quotation_id'],
                'company_id' => $data['company_id']
            ];

            # Update quotation status
            $updateQuotationStatus = Quotation::where($quotationFilter)->update(['is_order_pending' => 0]);

            # Return if fail
            if (!$updateQuotationStatus) {
                return Utility::apiError('Fail to update quotation status', [], 221);
            }

            # Mark pending quotation deleted
            $pendingFilter = ['unique_quotation_no' => $data['unique_quotation_no'], 'quotation_id' => $data['quotation_id']];

            $updatePendingQuotation = PendingQuotation::where($pendingFilter)->update(['status_code' => 'win', 'reason_status_id'=> 1]);

            # Return if fail
            if (!$updatePendingQuotation) {
                return Utility::apiError('Fail to update pending quotation', [], 221);
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
                'country' => $country->name ?? null,
                'date' => $data['order_date'] ?? null,
                'courier' => $courier->name ?? null,
                'payment_term' => $paymentTerm->payment_type ?? null,
                'preparing_by' => $data['prepard_by'] ?? null,
                'branch_address' => $branchAddress,
                'currency' => $currencyInfo,
                'file_path' => $pdfFilePath,
                'update_company_name' => $data['updated_company_name'] ?? null,
                'quotation_type' => $data['quotation_type'] ?? null,
                'email' => Auth::user()->email,
                'order_created_at' => now()->format('Y-m-d'),
                'overdue_no' => $data['overdue_number'] ?? null,
                'overdue_name' => $data['overdue_value'] ?? null,
                'order_prepared_by' => $data['order_prepared_by'] ?? null,
                'quotation_created_date' => $data['quotation_created_date'] ?? null,
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
            return Utility::apiSuccess('Order generated successfully', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail to generate order', ['exception' => $ex->getMessage()], 500);
        }
    }


    public function getOrder(Request $request)
    {
        try {
            // Collect only the relevant inputs
            $data = $request->only([
                'per_page',
                'branch_list',
                'owner_list',
                'currency_list',
                'quotation_status_list',
                'principal_list',
                'date_range',
                'search.value'
            ]);

            $perPage = $data['per_page'] ?? config('constant.per_page', 15);

            $query = Order::select([
                'id',
                'unique_order_no',
                'unique_quotation_no',
                'lead_from',
                'branch_id',
                'owner_id',
                'currency_id',
                'company_id',
                'branch_id',
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
                'lead_from',
                'notification_id',
                'quotation_type_id',
                'owner_id',
                'payment_term_condition',
                'date',
                'prepard_by',
                'pdf_name',
                'enq_ref',
                'currency_id',
                'company_id',
                'delivery_date_id',
                'total_amount',
                'customer_order_no'
            ])
                ->with([
                    'orderDetails',
                    'companyDetails:id,company_name,email_id',
                    'branchDetails:id,name',
                    'currencyDetails:id,code',
                    'pendingQuotationDetails:unique_quotation_no,quotation_id,reason,status_code,follow_up_date,total_amount,reason_status_id,last_updated_at'
                ])
                ->whereNull('deleted_at')
                ->when(
                    !empty($data['branch_list']),
                    fn($q) =>
                    $q->whereIn('branch_id', $data['branch_list'])
                )
                ->when(
                    !empty($data['owner_list']),
                    fn($q) =>
                    $q->whereIn('owner_id', $data['owner_list'])
                )
                ->when(
                    !empty($data['currency_list']),
                    fn($q) =>
                    $q->whereIn('currency_id', $data['currency_list'])
                )
                ->when(
                    !empty($data['quotation_status_list']),
                    fn($q) =>
                    $q->whereIn('is_order_pending', $data['quotation_status_list'])
                )
                ->when(
                    !empty($data['principal_list']),
                    fn($q) =>
                    $q->whereIn('principal_id', $data['principal_list'])
                )
                ->when(!empty($data['date_range']), function ($q) use ($data) {
                    [$from, $to] = explode('|', $data['date_range']);
                    $q->whereBetween('dt_date_created', [
                        Carbon::parse($from)->startOfDay(),
                        Carbon::parse($to)->endOfDay()
                    ]);
                })
                ->when(!empty($data['search.value']), function ($q) use ($data) {
                    $term = $data['search.value'];
                    $q->where(function ($sub) use ($term) {
                        $sub->where('in_quot_num', 'like', "%{$term}%")
                            ->orWhere('fl_nego_amt', 'like', "%{$term}%")
                            ->orWhere('lead_from', 'like', "%{$term}%")
                            ->orWhereHas(
                                'customer',
                                fn($c) =>
                                $c->where('st_com_name', 'like', "%{$term}%")
                            )
                            ->orWhereHas(
                                'quotationDetails',
                                fn($d) =>
                                $d->where('st_part_no', 'like', "%{$term}%")
                            );
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


    public function deletePendingQuotation($id)
    {
        try {
            if (Auth::user()->hasPermission('branch_all')) {
                $update_pending_infog = PendingQuotation::where('stn_qtn_ord_no', $id)->update(['is_deleted' => 1]);
            } else {
                $update_pending_infog = PendingQuotation::where(['stn_qtn_ord_no' => $id, 'int_branch_id' => Auth::user()->branch_id])->update(['is_deleted' => 1]);
            }
            if (!empty($update_pending_infog)) {
                return true;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }
    public function generateOrderNumber(string $branchName, int $branchId, string $quotationDate): ?string
    {
        try {
            # Get prefix
            $prefix = strtoupper(substr(trim($branchName), 0, 3));
            $date = Carbon::parse($quotationDate)->startOfDay();
            $formattedDate = $date->format('Ymd');

            # Get exsting order id
            $latestOrder = Order::where('branch_id', $branchId)
                ->whereNull('deleted_at')
                ->whereDate('created_at', $date)
                ->orderByDesc('id')
                ->first();

            # Default set
            $nextNumber = 1;

            # If order found
            if ($latestOrder && !empty($latestOrder['unique_order_id'])) {
                $parts = explode('-', $latestOrder['unique_order_id']);
                if (isset($parts[1]) && is_numeric($parts[1])) {
                    $nextNumber = (int) $parts[1] + 1;
                }
            }

            # Return order number
            return "{$prefix}/{$formattedDate}/order-{$nextNumber}";
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed generating order info', ['exception' => $ex->getMessage()]);
        }
    }
    public function orderPreview(Request $request)
    {
        try {
            # Preview Order
            $pdf = \App::make('dompdf.wrapper');
            $sel_prods_details = $request->sel_prods_details;
            if (!empty($sel_prods_details)) {
                $validator = Validator::make($sel_prods_details[0], [
                    'in_cust_id',
                ]);
            }
            $msg1 = $validator->getMessageBag()->toArray();
            $quotation_info = $request->quotation_info;
            if (!empty($quotation_info)) {
                $val = [
                    "st_shiping_add",
                    "st_shiping_city",
                    "st_shiping_state",
                    "st_shiping_pincode",
                    "st_shipping_email",
                    "st_shipping_phone",
                    "st_enq_ref_number",
                    'shipping_lanline',
                    "st_landline",
                    'product_search',
                    'prod_qty',
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
                    "st_com_name",
                    'order_no',
                    'order_date',
                    "auto_pop_cust_name",
                    "st_cust_mobile",
                    "auto_pop_state",
                    "preparing_by",
                    "lead_from",
                    'auto_pop_addr',
                    'auto_pop_state',
                    'auto_pop_city',
                    'auto_pop_pincod',
                    'auto_pop_phone',
                    'auto_pop_email',
                    'auto_pop_landline',
                ]);
            }
            $msg3 = $validator2->getMessageBag()->toArray();
            if ($validator1->fails() || $validator2->fails()) {
                $msg = $msg2 + $msg3;
                return Response::json(array(
                    'success' => false,
                    'errors' => $msg
                ), 400);
            }

            $indian_all_states = Config::get('constant.indian_all_states');
            if ($customer_info['country_code'] == 'IN') {
                $customer_info['auto_pop_state'] = $indian_all_states[$customer_info['auto_pop_state']];
                $quotation_info['st_shiping_state'] = $indian_all_states[$quotation_info['st_shiping_state']];
            }
            // if(Auth::user()->hasPermission('branch_all')){
            $result = [];
            $billing_address = $request->quotation_info;
            $format = $billing_address['bill_add_id'];
            // }
            $courier = Courier::get();
            if (count($courier) > 0) {
                $courier = $courier->pluck('st_courier_name', 'in_courier_id')->toArray();
            } else {
                $courier = [];
            }
            if (!empty($customer_info['country_code'])) {
                $country = Config::get('constant.countries');
                $customer_info['country'] = $country[$customer_info['country_code']];
            }
            $customer_info['courier'] = $courier[$customer_info['courier']];
            $customer_info['ext_note'] = $customer_info['ext_note'];
            $customer_info['quotation_created_date'] = $customer_info['quotation_created_date'];
            $result['order_details'] = $request->sel_prods_details;
            $result['customer_info'] = $customer_info;
            $result['order_info'] = $quotation_info;
            $result['BillAddress'] = $this->get_PDF_BillAddress();
            $cur = Config::get('constant.currency');
            $currencyCodes = Config::get('constant.currencyCodes');
            $qt_info = $request->quotation_info;
            $c_format = $quotation_info['currency'];
            $result['currency'] = !empty($currencyCodes[$cur[$c_format]]) ? $currencyCodes[$cur[$c_format]] : '';
            $quotation_type = $request->quotation_info['quotation_type'];
            if ($quotation_type == 'GW Quotation' || $quotation_type == 'Project Quotation') {
                $data['order_data'] = View::make("office.order.preview_prj_order", compact('result'))->render();
            } else {
                $data['order_data'] = View::make("office.order.preview_order", compact('result'))->render();
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
    public function deleteOrder(Request $request, $id, $quote_id)
    {
        try {
            // dd($id, $quote_id);
            # Remove order
            // if(Auth::user()->hasPermission('branch_all')){

            // }else{
            //     $records = Order::where(['in_order_id'=>$id, 'in_branch_id'=>Auth::user()->branch_id])->delete();
            // }
            $records = Order::where('in_order_id', $id)->delete();
            if ($records == 1) {
                // if(Auth::user()->hasPermission('branch_all')){
                //     $remove_order_details  = OrderDetails::where('in_order_id', $id)->delete(); 
                //     $update_quote = QuatationAdd::where('in_quot_id', $quote_id)->update(['is_order_pending'=>0]);
                //     $update_pending = PendingQuotation::where('int_qd_no', $quote_id)->update(['is_deleted'=>0]);
                // }else{
                //     $remove_order_details  = OrderDetails::where(['in_order_id'=>$id, 'branch_id'=>Auth::user()->branch_id])->delete();
                //     $update_quote = QuatationAdd::where(['in_quot_id'=>$quote_id, 'in_branch_id'=>Auth::user()->branch_id])->update(['is_order_pending'=>0]);
                //     $update_pending = PendingQuotation::where(['int_qd_no'=>$quote_id])->update(['is_deleted'=>0]);
                // }

                $remove_order_details = OrderDetails::where('in_order_id', $id)->delete();
                $update_quote = QuatationAdd::where('in_quot_id', $quote_id)->update(['is_order_pending' => 0]);
                $update_pending = PendingQuotation::where('int_qd_no', $quote_id)->update(['is_deleted' => 0]);
                $message = 'Order deleted successfully !';
            } else {
                $message = 'Fail to delete records !';
            }
            return back()->with([
                'message' => $message
            ]);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function closeOrder(Request $request)
    {
        try {
            # Extract only required fields
            $data = $request->only(['order_id']);

            # Validate request
            $validator = Validator::make($data, [
                'order_id' => 'required|integer|exists:orders,id'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Fetch order info
            $order = Order::with('customer', 'orderDetails', 'branchAddress')
                ->where('id', $data['order_id'])
                ->first();

            # Return if fail
            if (!$order) {
                return Utility::apiError('Order not found', [], 221);
            }

            # Prepare data
            $response = [
                'order_info' => $order,
                'order_details' => $order->orderDetails,
                'customer_info' => $order->customer,
                'billing_address_info' => $order->branchAddress,
            ];

            # Render the view as string for frontend preview if needed
            $htmlPreview = View::make("office.order.preview_close_order", $response)->render();

            # Return response
            return Utility::apiSuccess('Order preview data fetched successfully', [
                'preview_html' => $htmlPreview,
                'raw_data' => $response
            ]);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Server error while closing order', ['exception' => $ex->getMessage()], 500);
        }
    }
    public function closeOrderId(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['order_id']);

            # Validate request
            $validator = Validator::make($data, [
                'order_id' => 'required|integer|exists:orders,id'
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Ordero id
            $orderId = $data['order_id'];

            # Check if order already fully completed
            if ($this->checkPartialOrderStatus($orderId)) {
                return Utility::apiError('Order has been fully completed.', [], 200);
            }

            # Fetch order info
            $orderInfo = optional(Order::where('id', $orderId)
                ->when(!Auth::user()->hasPermission('branch_all'), fn($q) => $q->where('branch_id', Auth::user()->branch_id))
                ->whereNull('deleted_at')
                ->first())->toArray() ?: false;

            # Return if not match
            if (!is_array($orderInfo)) {
                return Utility::apiError('Please generate an order first.', [], 221);
            }

            # Get meta info
            $branchId = Auth::user()->branch_id;
            $adminUserId = Auth::user()->id;
            $customerId = $orderInfo['customer_id'];

            # Get order details
            $orderDetails = OrderDetails::where(['order_id' => $orderId])->whereNull('deleted_at')->get();

            # Return if not found
            if (!$orderDetails) {
                return Utility::apiError('Order detail not found', [], 221);
            }

            # Get branch name
            $branchMap = Branch::where('id', $branchId)->first();
            if (!$branchMap) {
                return Utility::apiError('Branch not found', [], 221);
            }

            $newOrderNo = $this->generatePartialOrderNumber($branchMap['name'], $branchId, 'partial-order');

            // Prepare partial order data
            $insertOrderArr = [
                'unique_partial_order_id' => $newOrderNo,
                'unique_quotation_id' => $orderInfo['unique_quotation_id'] ?? null,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'order_number' => $orderInfo['order_number'] ?? null,
                'order_date' => $orderInfo['order_date'],
                'address' => $orderInfo['address'] ?? null,
                'state_id' => $orderInfo['state_id'] ?? null,
                'other_state' => $orderInfo['other_state'] ?? null,
                'city' => $orderInfo['city'] ?? null,



                'st_partlyord_ship_pincode' => $orderInfo['st_ord_ship_pincode'],
                'in_partlyord_ship_tel' => $orderInfo['in_ord_ship_tel'],
                'st_landline' => $orderInfo['st_landline'],
                'st_partlyord_ship_email' => $orderInfo['st_ord_ship_email'],
                'flg_same_as_bill_add' => $orderInfo['flg_same_as_bill_add'],
                'st_ord_tin_no' => $orderInfo['st_ord_tin_no'],
                'st_pay_turm' => $orderInfo['st_pay_turm'],
                'st_ext_note' => trim($orderInfo['st_ext_note']),
                'flt_ord_saletax_id' => $orderInfo['flt_ord_saletax_id'],
                'flt_ord_net_total' => $orderInfo['flt_ord_net_total'],
                'flt_ord_saletax_amt' => $orderInfo['flt_ord_saletax_amt'],
                'flt_ord_frig_pack' => $orderInfo['flt_ord_frig_pack'],
                'flt_ord_total' => $orderInfo['flt_ord_total'],
                'lead_from' => $orderInfo['lead_from'],
                'int_ord_status' => 2,
                'log_in_id' => $adminUserId,
                'in_branch_id' => $branchId,
                'is_payment_paid' => 0,
                'st_courier_option' => $orderInfo['st_courier_option'],
                'dt_created' => now(),
                'st_cont_person_for_payment' => $orderInfo['st_cont_person_for_payment'],
                'int_cont_num_for_payment' => $orderInfo['int_cont_num_for_payment'],
            ];

            // Insert partial order
            $insertedOrderId = $this->isert_orders($insertOrderArr);
            if (!$insertedOrderId) {
                return Utility::apiError('Failed to generate partially order.');
            }

            // Insert order details
            foreach ($orderDetails as $item) {
                $orderDetail = [
                    'in_partparaint_ord_id' => $insertedOrderId,
                    'in_partlyorder_id' => (int) $orderId,
                    'st_part_no' => $item['st_part_no'],
                    'in_partlyord_prod_id' => 0,
                    'in_partlyord_pro_desc' => $item['in_ord_pro_desc'],
                    'in_partlyord_delivery_period' => $item['in_ord_delivery_period'],
                    'in_partlyord_pro_maker' => $item['in_ord_pro_maker'],
                    'in_partlyord_pro_qty' => $item['in_ord_pro_qty'],
                    'in_balance_pro_qty' => 0,
                    'in_sent_pro_qty' => $item['in_ord_pro_qty'],
                    'flt_partlyord_pro_price' => $item['flt_ord_pro_price'],
                    'flt_partlyord_pro_disct' => $item['flt_ord_pro_disct'],
                    'flt_partlyord_pro_net_price' => $item['flt_ord_pro_net_price'],
                    'flt_partlyord_pro_row_total' => $item['flt_ord_pro_row_total'],
                    'in_partlyord_pro_status' => 0,
                    'dt_created' => now()
                ];
                $this->isert_orders_details($orderDetail, $item['in_ord_detail_id']);
            }

            // Update main order and its details
            $this->update_order($orderId, [
                'flg_is_order_closed' => 1,
                'flg_deleted' => 0,
            ]);
            $this->update_part_order_detail_status($orderId, [
                'flg_partord_status' => 1,
                'flg_is_partial_checked' => 0,
            ]);

            // Clean up pending quotation and shipment status
            $this->deletePendingQuotation($orderInfo['in_uniq_order_id']);
            $this->update_shipment_order($orderInfo['in_uniq_order_id']);

            // Generate PDF data
            $pdfFilePath = "order_" . time() . "_" . date('dmy') . ".pdf";
            $jobPayload = [
                'order_id' => $orderId,
                'file_path' => $pdfFilePath,
                'order_info' => $this->get_partial_order_info($insertedOrderId, $customerId),
                'order_details' => $this->get_order_details_data($insertedOrderId, $customerId),
                'customer_info' => $this->get_customer_by_id($customerId),
                'email' => Auth::user()->email,
                'cc_email' => Auth::user()->cc_email,
                'format' => 'address', // Placeholder
            ];

            dispatch(new CloseOrder($jobPayload));

            return Utility::apiSuccess('Partially order generated successfully.', [
                'order_id' => $insertedOrderId,
                'partial_order_no' => $newOrderNo,
                'pdf' => $pdfFilePath,
            ]);
        } catch (\Exception $e) {
            \Log::error('closeOrderId error: ' . $e->getMessage());
            return Utility::apiError('Something went wrong.');
        }
    }

    public function addOrderReason(Request $request)
    {
        try {
            # Extract only necessary fields
            $data = $request->only([
                'quotation_id',
                'order_number',
                'order_value',
                'order_date',
                'customer_id',
                'reason_mode',
                'quotation_status',
                'quotation_text',
                'reason_id',
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'quote_id' => 'required|integer|exists:quotations,id',
                'order_number' => 'required|string',
                'order_value' => 'required|numeric',
                'order_date' => 'required|date',
                'customer_id' => 'required|integer|exists:customers,id',
                'reason_mode' => 'nullable|in:0,1',
                'status_quotation' => 'nullable|in:0,1',
                'status_quotation_text' => 'nullable|string',
                'reason_id' => 'nullable|sometimes',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Authenticated user
            $admin = Auth::user();

            # Initialize reason data
            $reasonMode = isset($data['reason_mode']) && $data['reason_mode'] == 1 ? 1 : 0;
            $stnReason = $data['reason_name'] ?? null;
            $isDeleted = 0;

            # Determine reason source
            if (isset($data['status_quotation'])) {
                if (!empty($data['status_quotation_text'])) {
                    Quotation::where('id', operator: $data['quotation_id'])->update([
                        'is_order_pending' => $data['status_quotation']
                    ]);
                    $stnReason = $data['status_quotation_text'];
                    $isDeleted = 1;
                }
            }

            # Prepare insert data
            $insertData = [
                'quotation_id' => $data['quote_id'] ?? null,
                'quotation_order_no' => $data['order_number'] ?? null,
                'amount' => $data['order_value'],
                'date' => Carbon::parse($data['order_date']),
                'customer_id' => $data['customer_id'] ?? null,
                'reason' => $stnReason,
                'reason_mode' => $reasonMode,
                'branch_id' => $admin['branch_id'] ?? null,
                'user_id' => $admin['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'is_deleted' => $isDeleted
            ];

            # Update reason 
            $reason = PendingQuotation::create($insertData);

            # Return response
            return Utility::apiSuccess('Reason added successfully', ['id' => $reason->id]);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Server error while adding reason', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function checkPartialOrderStatus($orderId)
    {
        try {
            # Get auth info
            $user = Auth::user();
            $conditions = ['id' => $orderId, 'deleted_at' => null];

            # Check permission
            if (!$user->hasPermission('branch_all')) {
                $conditions['branch_id'] = $user->branch_id;
            }

            # Check order status
            $hasAny = OrderDetails::where($conditions)->exists();
            $hasPending = OrderDetails::where($conditions)->where('partial_order_status', 0)->exists();

            # Return response
            return $hasAny ? !$hasPending : false;
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Server error while getting checkPartialOrderStatus', ['exception' => $ex->getMessage()], 500);
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

    public function generatePartialOrderNumber($branchName, $branchId, $context = '')
    {
        try {
            # Get prifix
            $prefix = substr($branchName, 0, 3) . '/' . ($context ? 'Part-' : '');

            # Get partial order latest info
            $query = PartialOrder::whereDate('created_at', Carbon::today());
            if (!Auth::user()->hasPermission('branch_all')) {
                $query->where('in_branch_id', $branchId);
            }

            # Update count
            $count = $query->count() + 1;

            # Return number
            return $prefix . $count;
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Server error while getting generatePartialOrderNumber', ['exception' => $ex->getMessage()], 500);
        }
    }
}