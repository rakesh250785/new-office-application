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
class PartialOrderController extends Controller
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
                "id",
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
                'unique_order_no',
                'customer_order_no',
                'extra_notes',
                'courier_id',
            ]);

            # Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:orders,id',
                'customer_order_no' => 'required|string',
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
                'product_list.*.send_qty' => 'required|numeric|min:0',
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
            $orderId = $data['id'];
            $branchId = Auth::user()['branch_id'];
            $adminUserId = Auth::id();
            $branchName = Branch::where('id', $branchId)->first();
            $pdfFilePath = "order_" . time() . "_" . date('dmy') . ".pdf";

            # Check if order fully completed
            $checkOrderStatus = OrderDetails::whereNull('deleted_at')->where('order_id', $orderId)->where('partial_order_status', 0)->count();

            if ($checkOrderStatus == 0) {
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
            $frightPack = 10;
            $salesTaxRate = 0;
            $salesTax = (((float) $data['total_amount'] + $frightPack) * $salesTaxRate) / 100;
            $finalTotal = ceil((float) $data['total_amount'] + $frightPack);

            # Update order totals 
            $orderTotalUpdate = Order::where('id', $orderId)->update([
                'total_amount' => (float) $data['total_amount'],
                'sale_tax_amount' => $salesTax,
                'final_total_amount' => $finalTotal,
            ]);

            if (!$orderTotalUpdate) {
                return Utility::apiError('Failed to update order toytal.', [], 221);
            }

            # Generate unique partial order number
            $dateStr = Carbon::now()->format('dmY');
            $initials = substr($branchName['name'], 0, 3);
            $partialCount = PartialOrder::where('branch_id', $branchId)->whereDate('created_at', Carbon::now()->format('Y-m-d'))->count();
            $uniquePartialNo = $initials . "/" . $dateStr . "/Part-" . ($partialCount + 1);

            $partialOrder = Carbon::now()->format('Y-m-d 00:00:00');

            # Insert new partial order
            $partialOrderData = [
                'unique_partial_order_no' => $uniquePartialNo,
                'unique_order_no' => $data['unique_order_no'] ?? null,
                'unique_quotation_no' => $data['unique_quotation_no'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'order_id' => $data['id'] ?? null,
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
                'date' => $partialOrder ?? null,
                'enq_ref' => $data['enq_ref'] ?? null,
                'prepard_by' => $data['prepard_by'] ?? null,
                'branch_id' => $branchId ?? null,
                'pdf_name' => $pdfFilePath ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'tin_number' => '27700707469',
                'user_id' => $adminUserId,
                'total_amount' => $data['total_amount'] ?? null,
                'customer_order_no' => $data['customer_order_no'] ?? null,
                'extra_notes' => $data['extra_notes'] ?? null,
                'courier_id' => $data['courier_id'] ?? null,
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
                $balanceQty = max(0, $item['quantity'] - $item['send_qty']);
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
                    'partial_order_id' => $partialOrderId['id'],
                    'order_id' => $orderId,
                    'quotation_id' => $data['quotation_id'],
                    'unique_order_no' => $data['unique_order_no'],
                    'unique_quotation_no' => $data['unique_quotation_no'],
                    'unique_partial_order_no' => $uniquePartialNo,
                    'product_id' => $data['product_id'] ?? 0,
                    'principal_id' => $data['principal_id'] ?? null,
                    'part_no' => $item['part_no'] ?? '',
                    'description' => $item['description'] ?? '',
                    'hsn_code' => $item['hsn_code'] ?? '',
                    'in_stock' => $item['in_stock'] ?? 0,
                    'send_qty' => $item['send_qty'] ?? 0,
                    'price' => $price,
                    'discount' => $discount,
                    'net_price' => $afterDiscount,
                    'igst' => $igst,
                    'balance_quantity' => $balanceQty ?? 0,
                    'order_type' => 1,
                    'quantity' => $item['quantity'] ?? 0,
                    'total' => $totalAmount,
                    'status' => 0,
                    'partial_order_status' => 0,
                    'notes' => $item['notes'] ?? null,
                    'product_specification' => $item['product_specification'] ?? null,
                    'delivery_date_id' => $item['delivery_date_id'] ?? 0,
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
                $updateStatus = OrderDetails::where('order_id', $data['id'])->where('id', $upd['id'])->update([
                    'balance_quantity' => $upd['balance_quantity'],
                ]);

                if (!$updateStatus) {
                    return Utility::apiError('Failed to update quantity.', [], 221);
                }
            }

            # Update order details status flags inline
            $orderStatus = OrderDetails::where('order_id', $orderId)
                ->where('balance_quantity', '<=', 0)
                ->update(['partial_order_status' => 1]);

            if ($orderStatus) {
                $statusCheck = PartialOrder::where('id', $partialOrderId)->update(['partial_order_status' => 0]);
                if (!$statusCheck) {
                    return Utility::apiError('Failed to update partial order status.', [], 221);
                }
            }
            # Close the order
            $updateCloseOrder = Order::where(['id' => $orderId, 'unique_order_no' => $data['unique_order_no']])->update([
                'customer_order_no' => $data['customer_order_no'],
                'is_order_closed' => '1',
                'is_shipment_pending' => '1',
                'pdf_name' => $pdfFilePath,
            ]);

            if (!$updateCloseOrder) {
                return Utility::apiError('Failed to update order status.', [], 221);
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

            return Utility::apiSuccess('generated successfully.', [], 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Fail to generate order', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getPartialOrder(Request $request)
    {
        try {
            # Collect only the relevant inputs
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

            $query = PartialOrder::select([
                'id',
                'unique_order_no',
                'unique_partial_order_no',
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
                'customer_order_no',
                'courier_id',
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

}
