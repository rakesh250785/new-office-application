<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Customer;
use App\Jobs\ProcessPartialOrder;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PartialOrder;
use App\Models\PartialOrderDetails;
use App\Models\PendingQuotation;
use App\Models\QuotationFormat;
use App\Models\States;
use Carbon\Carbon;
use Exception, Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class PartialController extends Controller
{
    public function __construct()
    {
    }

    public function storePartialOrder(Request $request)
    {
        try {
            # Request specific fields
            $data = $request->only([
                "product_id",
                "order_id",
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
                'order_id' => 'required|integer|exists:orders,id',
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

            # Auth user
            $orderId = $data['order_id'];
            $branchId = Auth::user()->branch_id;
            $adminUserId = Auth::id();
            $branchName = Branch::where('id', $branchId)->first();
            $pdfFilePath = "order_" . time() . "_" . date('dmy') . ".pdf";

            # Check if order fully completed
            $checkOrderStatus = OrderDetails::whereNull('deleted_at')->where('order_id', $orderId)->where('partial_order_status', 0)->count();

            if (count($checkOrderStatus) == 0) {
                return Utility::apiError('Order has been fully completed', [], 221);
            }

            # Update customer details
            $customerUpdate = Customer::where('id', $data['company_id'])->update([
                'address' => $data['billing_address'],
                'city' => $data['billing_city'],
                'pin_code' => $data['billing_pin_code'] ?? null,
                'state_id' => $data['billing_state_id'] ?? null,
                'other_state' => $data['other_state'] ?? null,
                'mobile_no' => $data['billing_mobile'] ?? null,
                'email_id' => $data['billing_email'] ?? null,
                'landline_no' => $data['billing_landline'] ?? null,
            ]);

            if (!$customerUpdate) {
                return Utility::apiError('Failed to update customer info.', [], 221);
            }

            # Calculate subtotal from order details once
            $subTotal = OrderDetails::where('order_id', $orderId)->sum('total');
            $frightPack = 10;
            $salesTaxRate = 0;
            $salesTax = (($subTotal + $frightPack) * $salesTaxRate) / 100;
            $finalTotal = ceil($subTotal + $frightPack);

            # Update order totals 
            $orderTotalUpdate = Order::where('order_id', $orderId)->update([
                'total_amount' => $subTotal,
                'sale_tax_amount' => $salesTax,
                'final_total_amount' => $finalTotal,
            ]);

            if (!$orderTotalUpdate) {
                return Utility::apiError('Failed to update order toytal.', [], 221);
            }

            # Generate unique partial order number
            $dateStr = Carbon::now()->format('dmY');
            $initials = substr($branchName, 0, 3);
            $partialCount = PartialOrder::where('branch_id', $branchId)->whereDate('created_at', Carbon::now()->format('Y-m-d'))->count();
            $uniquePartialNo = $initials . "/" . $dateStr . "/Part-" . ($partialCount + 1);

            # Insert new partial order
            $partialOrderData = [
                'unique_partial_order_no' => $uniquePartialNo,
                'unique_order_no' => $data['unique_order_no'] ?? null,
                'unique_quotation_no' => $data['unique_quotation_no'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'billing_city' => $data['billing_city'] ?? null,
                'billing_mobile' => $data['billing_mobile'] ?? null,
                'billing_email' => $data['billing_email'] ?? null,
                'billing_landline' => $data['billing_landline'] ?? null,
                'billing_pin_code' => $data['billing_pin_code'] ?? null,
                'billing_state_id' => $data['billing_state_id'] ?? null,
                'billing_contact_person' => $data['contact_person'] ?? null,
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
                'user_id' => $adminUserId,
                'total_amount' => $data['total_amount'] ?? null,
                'customer_order_no' => $data['customer_order_no'] ?? null,
            ];

            # Insert partial order data
            $partialOrderId = PartialOrder::create($partialOrderData);
            if (!$partialOrderId) {
                return Utility::apiError('Failed to create partial order.', [], 221);
            }

            # Prepare bulk insert for partial order details
            $grandTotal = 0;
            $calculations = [];
            $productList = [];

            foreach ($data['product_list'] as $item) {
                $balanceQty = max(0, $item['balance_qyantity'] - $item['send_quantity']);
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
                    'partial_order_id' => $partialOrderId,
                    'order_id' => $orderId,
                    'quotation_id' => $data['quotation_id'],
                    'unique_order_no' => $data['unique_order_no'],
                    'unique_quotation_no' => $data['unique_quotation_no'],
                    'unique_partial_order_no' => $data['unique_partial_order_no'],
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
                    'balance_quantity' => $balanceQty ?? 0,
                    'order_type' => 1,
                    'quantity' => $item['send_quantity'],
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
                $orderDetailsToUpdate[] = [
                    'id' => $item['id'],
                    'balance_quantity' => $balanceQty,
                ];
            }

            # Add partial order details
            $partialOrderDetails = PartialOrderDetails::insert($productList);
            if (!$partialOrderDetails) {
                return Utility::apiError('Failed to create partial order details.', [], 221);
            }

            # Bulk update order details balances and sent qty
            foreach ($orderDetailsToUpdate as $upd) {
                $updateStatus = OrderDetails::where('order_detail_id', $upd['id'])->update([
                    'quantity' => 0,
                    'balance_quantity' => $upd['balance_quantity'],
                ]);

                if (!$updateStatus) {
                    return Utility::apiError('Failed to update quantity.', [], 221);
                }
            }

            # Update order details status flags inline
            $orderDetailStatusUpdate = OrderDetails::where('order_id', $orderId)
                ->where('balance_quantity', '<=', 0)
                ->update(['partial_order_status' => 1]);

            if (!$orderDetailStatusUpdate) {
                return Utility::apiError('Failed to update order status.', [], 221);
            }


            # Close the order
            $updateCloseOrder = Order::where(['id' => $orderId, 'unique_order_no' => $data['unique_order_no']])->update([
                'cutomer_order_no' => $data['customer_order_no'],
                'is_order_closed' => true,
                'is_shipment_pending' => 1,
                'stn_pdf_name' => $pdfFilePath,
            ]);

            if (!$updateCloseOrder) {
                return Utility::apiError('Failed to update order status.', [], 221);
            }

            # Mark pending quotation deleted
            $updatePendingStatus = PendingQuotation::where(['quotation_id' => $data['quotation_id'], 'unique_quotation_no' => $data['unique_quotation_no']])->update(['deleted_at' => Carbon::now()]);
            if (!$updatePendingStatus) {
                return Utility::apiError('Failed to update pending status.', [], 221);
            }

            # Customer and currency info
            $customerInfo = Customer::findOrFail($data['company_id']);
            $currencyInfo = Currency::findOrFail($data['currency_id']);

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

            // dispatch(new ProcessPartialOrder($responsePayload));

            return response()->json(['code' => 200, 'success' => 'Partially order generated successfully.']);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail to generate order', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function generate_partially_order_no($branchname, $in_branch_id, $generate_No_for = "")
    {
        try {
            $initial3latters = substr($branchname, 0, 3);
            if (Auth::user()->hasPermission('branch_all')) {
                $partialOrder = PartialOrder::whereDate('dt_created', '>=', Carbon::now()->format('Y-m-d'))->get();
            } else {
                $partialOrder = PartialOrder::where('in_branch_id', $in_branch_id)->whereDate('dt_created', '>=', Carbon::now()->format('Y-m-d'))->get();
            }
            $flg_type = '';
            if ($generate_No_for != "") {
                $flg_type = "Part-";
            }
            if (!empty($partialOrder)) {
                $number = count($partialOrder) + 1;
                $unique_quote_no = $initial3latters . "/" . $flg_type . $number;
            } else {
                $unique_quote_no = $initial3latters . "/" . $flg_type . "1";
            }
            return $unique_quote_no;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function isert_orders($insert_array)
    {
        try {
            $insert = PartialOrder::insertGetId($insert_array);
            if (!empty($insert)) {
                return $insert;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function get_order_details_data($order_id, $in_cust_id)
    {
        try {
            if (Auth::user()->hasPermission('branch_all')) {
                $partial_order_details = PartialOrderDetails::where('in_partparaint_ord_id', $order_id)->where('flg_deleted', 0)->get();
            } else {
                $partial_order_details = PartialOrderDetails::where(['in_partparaint_ord_id' => $order_id, 'branch_id' => Auth::user()->branch_id])->where('flg_deleted', 0)->get();
            }
            return $partial_order_details;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function get_partial_order_info($order_id, $in_cust_id)
    {
        try {
            $result = [];
            if (Auth::user()->hasPermission('branch_all')) {
                $query = PartialOrder::where('in_partparaint_ord_id', $order_id)->where('in_cust_id', $in_cust_id)->where('flg_deleted', 0)->first();
            } else {
                $query = PartialOrder::where(['in_partparaint_ord_id' => $order_id, 'in_branch_id' => Auth::user()->branch_id])->where('in_cust_id', $in_cust_id)->where('flg_deleted', 0)->first();
            }
            if (!empty($query)) {
                $result = $query->toArray();
            }
            return $result;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

}
