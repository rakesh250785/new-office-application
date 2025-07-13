<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\PartialOrder;
use App\Models\PaymentTerm;
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

    public function getQuotationOrder(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only([
                'quotation_id',
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'quotation_id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            # Get quotation for order
            $quotation = Quotation::with([
                'customer',
                'details',
                'owner',
                'pending'
            ])->find($data['quotation_id']);

            # Return if not found
            if (!$quotation) {
                return Utility::apiError('Quotation not found.');
            }

            # Meta info
            $branchId = Auth::user()->branch_id;
            $branchWise = Branch::get()->pluck('name', 'id');
            $branchName = $branchWise[$branchId] ?? null;
            $quotationDate = Carbon::now()->format('Y-m-d 00:00:00');

            # Quotation data
            $data = [
                'quotation_info' => $quotation,
                'quotation_created_date' => $quotation['created_at'],
                'quotation_id' => $quotation['id'],
                'unique_quotation_number' => $quotation['unique_quotation_number'],
                'customer_id' => $quotation['customer_id'],
                'owner_id' => $quotation['owner_id'],
                'currency_id' => $quotation['currency_id'],
                'enqury_reference_number' => $this->generateOrderNumber($branchName, $branchId, $quotationDate),
            ];

            # Return response
            return Utility::apiSuccess('Order data fetched successfully', $data, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed fetching getQuotationOrder server error', ['exception' => $ex->getMessage()]);
        }
    }

    public function addUpdateOrder(Request $request)
    {
        try {
            # Extract request
            $data = $request->only([
                'order_id',
                'customer_id',
                'quotation_id',
                'unique_quotation_id',
                'lead_from',
                'order_date',
                'order_number',
                'quotation_date',
                'order_prepared_by',
                'contact_persion',
                'billing_info',
                'courier_id',
                'notes',
                'term_condition_notes',
                'shipment_invoice_id',
                'overdue_number',
                'overdue_value',
                'product_details',
                'shipping_info',
                'quotation_type',
                'enquiry_refefence_number',
                'net_total_amount',
                'sales_tax_amount',
                'order_total_amount',
                'updated_company_name',
                'currency_id',
                'quotation_created_date'
            ]);

            # Auth + Branch
            $admin = Auth::user();
            $branchId = $admin->branch_id;

            # Fetch master data
            $customerInfo = Customer::find($data['customer_id']);
            $branch = Branch::find($branchId);
            $country = Country::find(optional($customerInfo)->country_id);
            $state = States::find(optional($customerInfo)->state_id);
            $paymentTerm = PaymentTerm::find($data['shipment_invoice_id']);
            $courier = Courier::find($data['courier_id']);
            $currency = Currency::find($data['currency_id']);
            $branchAddress = QuotationFormat::whereNull('deleted_at')->where('branch_id', $branchId)->first();

            # Validate lookups
            if (!$customerInfo || !$branch || !$country || !$state || !$paymentTerm || !$courier || !$currency || !$branchAddress) {
                return Utility::apiError('Master data missing', [], 221);
            }

            # Parse shipping/billing
            $shipping = $data['shipping_info'] ?? [];
            $billing = $data['billing_info'] ?? [];

            # Determine state
            $withingState = ($country->code === 'in') ? $customerInfo->state_id : null;
            $otherState = $customerInfo->other_state;

            # Generate file name
            $pdfFilePath = 'order_' . time() . '_' . now()->format('dmy') . '.pdf';

            # Core order data
            $orderData = [
                'unique_quotation_id' => $data['unique_quotation_id'],
                'customer_id' => $data['customer_id'],
                'order_number' => $data['order_number'],
                'order_date' => $data['order_date'],
                'shipping_address' => $shipping['address'] ?? null,
                'state_id' => $shipping['state_id'] ?? null,
                'state' => $withingState,
                'other_state' => $otherState,
                'pincode' => $shipping['pincode'] ?? null,
                'city' => $shipping['city'] ?? null,
                'mobile' => $shipping['mobile'] ?? null,
                'landline' => $shipping['landline'] ?? null,
                'email' => $shipping['email'] ?? null,
                'same_as_billing' => $shipping['same_as_billing'] ?? null,
                'delivery_period' => 30,
                'lead_from' => $data['lead_from'],
                'branch_id' => $branchId,
                'term_condition_notes' => $data['term_condition_notes'],
                'order_prepared_by' => $data['order_prepared_by'],
                'quotation_type' => $data['quotation_type'],
                'user_id' => $admin->id,
                'pdf_name' => $pdfFilePath,
                'order_status' => 0,
                'currency_id' => $data['currency_id'],
                'contact_for_payment' => $shipping['mobile'] ?? null,
                'contact_person_for_payment' => $billing['mobile'] ?? null,
                'enquiry_refefence_number' => $data['enquiry_refefence_number'],
                'net_total_amount' => $data['net_total_amount'],
                'tax_branch_id' => 0,
                'sales_tax_amount' => $data['sales_tax_amount'],
                'order_fridge_package' => 0,
                'order_total_amount' => $data['order_total_amount'],
            ];

            # Add mode
            if (empty($data['order_id'])) {
                $orderData['unique_order_id'] = $this->generateOrderNumber($branch->name, $branchId, now()->toDateString());
                $order = Order::create($orderData);
            } else {
                $order = Order::find($data['order_id']);
                if (!$order)
                    return Utility::apiError('Order not found for update', [], 221);
                $order->update($orderData);
            }

            # Update customer info
            $customerUpdate = Customer::where('id', $customerInfo->id)->update([
                'address' => $billing['address'] ?? null,
                'city' => $billing['city'] ?? null,
                'contact_person' => $billing['name'] ?? null,
                'pincode' => $billing['pincode'] ?? null,
                'state_id' => $withingState,
                'other_state' => $otherState,
                'mobile' => $billing['mobile'] ?? null,
                'email' => $billing['email'] ?? null,
            ]);

            # Return if fail
            if (!$customerUpdate) {
                return Utility::apiError('Fail to update customer info', [], 221);
            }

            # Sync order details
            $orderDetailDeleteStatus = OrderDetails::where('order_id', $order->id)->delete();

            # Return if fail
            if (!$orderDetailDeleteStatus) {
                return Utility::apiError('Fail to delete exiting order details', [], 221);
            }

            # Define product
            $quotationData = [];
            $productData = [];
            $calcDetails = [];
            $grandTotal = 0;

            foreach ($data['product_details'] as $prod) {
                unset($prod['balance_quantity']);
                $quotationData[] = array_merge($prod, [
                    'quotation_id' => $data['quotation_id'],
                    'customer_id' => $data['customer_id'],
                ]);
                $productData[] = [
                    'order_id' => $order->id,
                    'product_id' => $prod['product_id'],
                    'description' => $prod['description'],
                    'principal_id' => $prod['principal_id'],
                    'order_quantity' => $prod['quantity'],
                    'price' => $prod['price'],
                    'discount' => $prod['discount'],
                    'net_price' => $prod['net_price'],
                    'total' => $prod['total'],
                    'balance_quantity' => $prod['balance_quantity'] ?? 0,
                    'order_type' => 1,
                    'delevery_period' => $prod['delevery_period'],
                    'comments' => $prod['comments'],
                    'part_number' => $prod['part_number'],
                    'hsn_number' => $prod['hsn_number'],
                    'igst_rate' => $prod['igst_rate'],
                    'quotation_type' => $data['quotation_type'],
                    'term_condition_notes' => $data['term_condition_notes'],
                    'uom' => $prod['uom'],
                    'moc' => $prod['moc'],
                    'specification' => $prod['specification'],
                    'product_head' => $prod['product_head'],
                    'status' => 0,
                    'partial_order_status' => 0,
                ];

                $baseAmount = $prod['unit_price'] * $prod['order_quantity'];
                $discountAmount = ($baseAmount * $prod['discount']) / 100;
                $afterDiscount = $baseAmount - $discountAmount;
                $gstAmount = ($afterDiscount * $prod['igst_rate']) / 100;
                $totalAmount = $afterDiscount + $gstAmount;
                $calcDetails[] = [
                    'base_amount' => $baseAmount,
                    'discount_amount' => $discountAmount,
                    'net_price' => $afterDiscount,
                    'gst_amount' => $gstAmount,
                    'total' => $totalAmount,
                ];
                $grandTotal += $totalAmount;
            }

            # Insert order details
            $orderDetailInsert = OrderDetails::insert($productData);

            # Return if fail
            if (!$orderDetailInsert) {
                return Utility::apiError('Fail to insert updated order details', [], 221);
            }

            # Refresh quotation product details
            $quotationDetailDelete = QuotationDetail::where('quotation_id', $data['quotation_id'])->delete();

            # Return if fail
            if (!$quotationDetailDelete) {
                return Utility::apiError('Fail to delete quotation details', [], 221);
            }

            # Insert quotation details
            $insertStatus = QuotationDetail::insert($quotationData);

            # Return if fail
            if (!$insertStatus) {
                return Utility::apiError('Fail to delete quotation details', [], 221);
            }

            # Update quotation flags
            $quotationFilter = [
                'id' => $data['quotation_id'],
                'customer_id' => $data['customer_id']
            ];
            if (!$admin->hasPermission('branch_all'))
                $quotationFilter['branch_id'] = $branchId;

            # Update quotation status
            $updateQuotationStatus = Quotation::where($quotationFilter)->update(['is_order_pending' => 1]);

            # Return if fail
            if (!$updateQuotationStatus) {
                return Utility::apiError('Fail to update quotation status', [], 221);
            }

            # Mark pending quotation deleted
            $pendingFilter = ['unique_quotation_id' => $data['unique_quotation_id']];
            if (!$admin->hasPermission('branch_all'))
                $pendingFilter['branch_id'] = $branchId;
            $updatePendingQuotation = PendingQuotation::where($pendingFilter)->update(['deleted_at' => now()]);

            # Return if fail
            if (!$updatePendingQuotation) {
                return Utility::apiError('Fail to update pending quotation', [], 221);
            }

            # Update quotation status
            $updateQuotationSatatus = Quotation::where($pendingFilter)->update(['order_pending' => 1]);

            # Return if fail
            if (!$updateQuotationSatatus) {
                return Utility::apiError('Fail to update quotation status', [], 221);
            }

            # Prepare PDF
            $pdfInfo = [
                'state' => $country->code === 'in' ? ($state->name ?? null) : null,
                'customer_info' => $customerInfo->toArray(),
                'country' => $country->name ?? null,
                'date' => $data['order_date'] ?? null,
                'payment_term' => $paymentTerm->payment_type ?? null,
                'courier' => $courier->name ?? null,
                'update_company_name' => $data['updated_company_name'] ?? null,
                'overdue_no' => $data['overdue_number'] ?? null,
                'overdue_name' => $data['overdue_value'] ?? null,
                'ext_note' => $data['notes'] ?? null,
                'quotation_created_date' => $data['quotation_created_date'] ?? null,
                'currency' => $currency->code ?? null,
                'order_info' => $orderData,
                'order_details' => $productData,
                'branch_adddress' => $branchAddress['address'] ?? $branchAddress['branch_address'] ?? null,
                'order_created_at' => now()->format('Y-m-d'),
                'order_prepared_by' => $data['order_prepared_by'] ?? null,
                'file_path' => $pdfFilePath,
                'email' => $admin->email ?? null,
                'cc_email' => $admin->cc_email ?? null,
                'quotation_type' => $data['quotation_type'] ?? null,
                'multi_prod_cal' => [
                    'calculations' => $calcDetails,
                    'grand_total' => $grandTotal
                ],
                'total_cal' => $grandTotal,
            ];

            # Dispatch job for pdf
            dispatch(new ProcessOrder($pdfInfo));

            # Return response
            return Utility::apiSuccess($data['order_id'] ? 'Order updated successfully' : 'Order created successfully', [], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed to process order', ['exception' => $ex->getMessage()]);
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
    public function getOrder(Request $request)
    {
        try {

            # Get order list
            $data = Order::with([
                'customer:id,name,mobile,email',
                'quotation:id,unique_quotation_id,order_pending',
                'details:id,order_id,part_number,description,principal_id,price,discount,quantity',
                'pendingQuotation:id,order_no,reason,created_at'
            ])
                ->whereNull('deleted_at')
                ->when(!Auth::user()->hasPermission('branch_all'), fn($q) => $q->where('branch_id', Auth::user()->branch_id))
                ->when($request->branch_id, fn($q, $branchId) => $q->where('branch_id', (int) $branchId))
                ->when($request->date_range, function ($q, $range) {
                    [$from, $to] = explode('|', $range);
                    $start = Carbon::parse($from)->startOfDay();
                    $end = Carbon::parse($to)->endOfDay();
                    $q->whereBetween('created_at', [$start, $end]);
                })
                ->when($request->search['value'] ?? null, function ($q, $term) {
                    $q->whereHas('customer', fn($q) => $q->where('name', 'like', "%$term%"))
                        ->orWhere('order_id', 'like', "%$term%")
                        ->orWhere('order_number', 'like', "%$term%")
                        ->orWhere('total_amount', 'like', "%$term%")
                        ->orWhere('lead_from', 'like', "%$term%")
                        ->orWhereHas('details', fn($q) => $q->where('part_number', 'like', "%$term%"));
                });

            # Return response
            return Utility::apiSuccess('Order list', $data, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed at getOrder info', ['exception' => $ex->getMessage()]);
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