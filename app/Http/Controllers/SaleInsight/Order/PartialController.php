<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PartialOrder;
use Carbon\Carbon;
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
                'order_id'=>'required|integer|exists:orders,id',
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

            // // Unpack all necessary data once
            // $customer = $req['customer_details'];
            // $billing = $req['billing_detail'];
            // $shipping = $req['shipping_details'];
            // $other = $req['other_details'];
            // $selProds = $req['sel_prods_details'];

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

            # Calculate subtotal from order details once
            $subTotal = OrderDetails::where('order_id', $orderId)->sum('total');
            $frightPack = 10;
            $salesTaxRate = 0;
            $salesTax = (($subTotal + $frightPack) * $salesTaxRate) / 100;
            $finalTotal = ceil($subTotal + $frightPack);

            # Update order totals 
            Order::where('order_id', $orderId)->update([
                'total_amount' => $subTotal,
                'sale_tax_amount' => $salesTax,
                'final_total_amount' => $finalTotal,
            ]);

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
            $partialOrderId = PartialOrder::insertGetId($partialOrderData);
            if (!$partialOrderId) {
                return response()->json(['code' => 400, 'error' => 'Failed to create partial order.']);
            }

            // Prepare bulk insert for partial order details
            $orderDetailsInsert = [];
            $orderDetailsToUpdate = [];

            foreach ($selProds as $prod) {
                $balanceQty = max(0, $prod['bal_qty'] - $prod['send_prod_qty']);
                $orderDetailsInsert[] = [
                    'in_partparaint_ord_id' => $partialOrderId,
                    'in_partlyorder_id' => (int) $orderId,
                    'st_part_no' => $prod['prod_part_No'],
                    'in_partlyord_prod_id' => 0,
                    'in_partlyord_pro_desc' => $prod['prod_desc'],
                    'in_partlyord_delivery_period' => 0,
                    'in_partlyord_pro_maker' => 'manoj',
                    'in_partlyord_pro_qty' => $prod['in_pro_qty'],
                    'in_balance_pro_qty' => $balanceQty,
                    'in_sent_pro_qty' => $prod['send_prod_qty'],
                    'flt_partlyord_pro_price' => $prod['prod_unit_price'],
                    'flt_partlyord_pro_disct' => $prod['prod_discount'],
                    'flt_partlyord_pro_net_price' => $prod['prod_net_price'],
                    'in_igst_rate' => $prod['prod_igst_rate'],
                    'flt_partlyord_pro_row_total' => $prod['prod_row_total'],
                    'in_partlyord_pro_status' => 0,
                    'dt_created' => $now,
                ];

                if (!empty($prod['in_ord_detail_id'])) {
                    $orderDetailsToUpdate[] = [
                        'id' => $prod['in_ord_detail_id'],
                        'bal_qty' => $balanceQty,
                    ];
                }
            }

            PartialOrderDetails::insert($orderDetailsInsert);

            // Bulk update order details balances and sent qty
            foreach ($orderDetailsToUpdate as $upd) {
                OrderDetails::where('in_ord_detail_id', $upd['id'])->update([
                    'in_ord_pro_sent_qty' => 0,
                    'in_ord_pro_bal_qty' => $upd['bal_qty'],
                ]);
            }

            // Update order details status flags inline
            OrderDetails::where('in_order_id', $orderId)
                ->where('in_ord_pro_bal_qty', '<=', 0)
                ->update(['flg_partord_status' => 1, 'flg_is_partial_checked' => 0]);

            OrderDetails::where('in_order_id', $orderId)
                ->where('in_ord_pro_bal_qty', '>', 0)
                ->update(['flt_ord_pro_row_total' => 0]);

            // Close the order
            Order::where('in_order_id', $orderId)->update([
                'st_cust_order_num' => $customer['cust_order_no'],
                'flg_is_order_closed' => 1,
            ]);

            // Update customer billing info once
            Customer::where('in_cust_id', $customer['customer_id'])->update([
                'st_com_address' => $billing['auto_pop_addr'],
                'st_cust_city' => $billing['auto_pop_city'],
                'in_pincode' => $billing['auto_pop_pincod'],
                'st_cust_state' => $billing['auto_pop_state'],
                'st_cust_mobile' => $billing['auto_pop_phone'],
                'st_cust_email' => $billing['auto_pop_email'],
            ]);

            // Mark pending quotation deleted
            PendingQuotation::where('stn_qtn_ord_no', $customer['unique_order_id'])->update(['is_deleted' => 1]);

            // Mark shipment pending & update PDF file path
            Order::where('in_uniq_order_id', $customer['unique_order_id'])->update([
                'is_shipment_pending' => 1,
                'stn_pdf_name' => $pdfFilePath,
            ]);

            // Fetch required data for PDF generation in one go
            $updatedState = Customer::where('in_cust_id', $customer['customer_id'])->first()->toArray();
            $updatedShipping = PartialOrder::where('in_partparaint_ord_id', $partialOrderId)
                ->where('flg_deleted', 0)->first()->toArray();

            $couriers = Courier::where('is_deleted', 0)->pluck('st_courier_name', 'in_courier_id')->toArray();
            $countries = config('constant.countries');
            $indianStates = config('constant.indian_all_states');
            $currencyCodes = config('constant.currencyCodes');
            $currencies = config('constant.currency');
            $quotation = QuatationAdd::where('in_quot_num', $customer['qoute_no'])
                ->select('dt_date_created', 'st_currency_applied')->first();

            // Map states and countries properly
            if (($updatedState['st_country'] ?? '') === 'IN') {
                $updatedShipping['st_ord_ship_state'] = $indianStates[$updatedState['st_cust_state']] ?? '';
                $updatedState['auto_pop_state'] = $updatedShipping['st_ord_ship_state'];
            } else {
                $updatedShipping['st_ord_ship_state'] = $shipping['shipping_state'];
            }

            $updatedState['country'] = $countries[$updatedState['st_country']] ?? '';
            $updatedState['courier'] = $couriers[$other['courier']] ?? '';

            $currency = $currencyCodes[$currencies[$quotation->st_currency_applied] ?? ''] ?? '';

            // Prepare PDF data object
            $pdfData = [
                'order_info' => $updatedShipping,
                'customer_info' => $updatedState,
                'BillAddress' => Quatation::where(['is_deleted' => 0, 'int_branch_id' => $branchId])
                    ->value('stn_branch_add') ?? '',
                'orderCreateDate' => $now->format('d-m-Y'),
                'preparing_by' => $customer['preparing_by'] ?? '',
                'st_pay_turm' => $updatedShipping['st_pay_turm'] ?? '',
                'file_path' => $pdfFilePath,
                'currency' => $currency,
                'email' => auth()->user()->email,
                'cc_email' => auth()->user()->cc_email,
                'ext_note' => $other['ext_note'],
                'quotation_created_date' => $quotation->dt_date_created ?? '',
                'order_details' => PartialOrderDetails::where([
                    'in_partparaint_ord_id' => $partialOrderId,
                    'flg_deleted' => 0,
                ])->get()->map(function ($prd) {
                    return [
                        'in_ord_pro_desc' => $prd->in_partlyord_pro_desc,
                        'in_ord_pro_qty' => $prd->in_partlyord_pro_qty,
                        'flt_ord_pro_price' => $prd->flt_partlyord_pro_price,
                        'flt_ord_pro_disct' => $prd->flt_partlyord_pro_disct,
                        'flt_ord_pro_net_price' => $prd->flt_partlyord_pro_net_price,
                        'flt_ord_pro_row_total' => $prd->flt_partlyord_pro_row_total,
                        'in_ord_delivery_period' => $prd->in_partlyord_delivery_period,
                        'product_comments' => '',
                        'st_part_no' => $prd->st_part_no,
                        'st_hsn_no' => '',
                        'in_igst_rate' => $prd->in_igst_rate,
                    ];
                }),
            ];

            dispatch(new Orders($pdfData));

            return response()->json(['code' => 200, 'success' => 'Partially order generated successfully.']);
        } catch (Exception $ex) {
            Log::error($ex);
            return response()->json(['code' => 500, 'error' => 'Internal Server Error']);
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
