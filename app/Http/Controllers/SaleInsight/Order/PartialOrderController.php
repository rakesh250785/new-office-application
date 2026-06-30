<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Exports\PartialOrderExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPartialOrder;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PartialOrder;
use App\Models\PartialOrderDetails;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Log;

class PartialOrderController extends Controller
{
    public function __construct() {}

    public function storePartialOrder(Request $request)
    {
        try {

            $data = $request->only([
                'id', 'product_id', 'product_description', 'principal_type', 'payment_term_condition',
                'lead_from', 'billing_address', 'billing_city', 'billing_state_id', 'billing_mobile',
                'billing_email', 'billing_landline', 'billing_pin_code', 'contact_person', 'shipping_address',
                'shipping_city', 'shipping_state_id', 'shipping_email', 'company_id', 'quotation_type_id',
                'notification_id', 'owner_id', 'order_id', 'date', 'enq_ref', 'prepard_by', 'currency_id',
                'shipping_pin_code', 'shipping_mobile', 'shipping_email', 'shipping_landline',
                'delivery_date_id', 'product_list', 'update_status', 'quotation_id', 'total_amount',
                'unique_quotation_no', 'unique_order_no', 'customer_order_no', 'extra_notes', 'courier_id',
                'unique_partial_order_no', 'partial_order_id', 'close_order_status',
            ]);

            $validator = Validator::make($data, [
                'partial_order_id' => 'sometimes|nullable|exists:partial_orders,id',
                'order_id' => 'required|exists:orders,id',
                'customer_order_no' => 'required|string',
                'unique_quotation_no' => 'required|string',
                'product_description' => 'nullable|string',
                'payment_term_condition' => 'required|string',
                'lead_from' => 'required|string|max:255',
                'billing_address' => 'required|string|max:500',
                'billing_city' => 'required|string|max:255',
                'billing_state_id' => 'nullable|integer|exists:states,id',
                'billing_mobile' => 'required|string|max:15',
                'billing_email' => 'required|email|max:255',
                'billing_landline' => 'required|string|max:15',
                'billing_pin_code' => 'required|string|max:10',
                'contact_person' => 'required|string|max:255',
                'shipping_address' => 'required|string|max:500',
                'shipping_city' => 'required|string|max:255',
                'shipping_state_id' => 'nullable|integer|exists:states,id',
                'shipping_pin_code' => 'required|string|max:10',
                'shipping_mobile' => 'required|string|max:15',
                'shipping_email' => 'required|email|max:255',
                'shipping_landline' => 'required|string|max:15',
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
                'product_list' => 'required|array|min:1',
                'product_list.*.part_no' => 'required|string|max:255',
                'product_list.*.description' => 'required|string|max:1000',
                'product_list.*.hsn_code' => 'required|string|max:50',
                'product_list.*.quantity' => 'required|numeric|min:1',
                'product_list.*.in_stock' => 'nullable|numeric|max:255',
                'product_list.*.price' => 'required|numeric|min:0',
                'product_list.*.send_qty' => 'nullable|numeric|min:0',
                'product_list.*.discount' => 'nullable|numeric|min:0|max:100',
                'product_list.*.net_price' => 'required|numeric|min:0',
                'product_list.*.igst' => 'nullable|numeric|min:0',
                'product_list.*.total' => 'required|numeric|min:0',
                'product_list.*.notes' => 'nullable|string|max:1000',
                'total_amount' => 'required|numeric|min:0',
                'courier_id' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            // Common data
            $branchId = Auth::user()['branch_id'];
            $adminUserId = Auth::id();
            $branch = Branch::find($branchId);
            $branchInitials = $branch ? substr($branch->name, 0, 3) : 'BR-';
            $pdfFilePath = now()->year.'/order_'.time().'_'.date('dmy').'.pdf';

            // Update customer
            $customerUpdate = Customer::where('id', $data['company_id'])->update([
                'address' => $data['billing_address'] ?? null,
                'city' => $data['billing_city'] ?? null,
                'pin_code' => $data['billing_pin_code'] ?? null,
                'state_id' => $data['billing_state_id'] ?? null,
                'other_state' => $data['other_state'] ?? null,
                'mobile_no' => $data['billing_mobile'] ?? null,
                'email_id' => $data['billing_email'] ?? null,
                'landline_no' => $data['billing_landline'] ?? null,
            ]);
            if (! $customerUpdate) {
                return Utility::apiError('Failed to update customer info.', [], 221);
            }

            // Simple tax calculations (kept as-is)
            $frightPack = 10;
            $salesTaxRate = 0;
            $salesTax = (((float) $data['total_amount'] + $frightPack) * $salesTaxRate) / 100;
            $finalTotal = ceil((float) $data['total_amount'] + $frightPack);

            // Update order totals
            $orderTotalUpdate = Order::where('id', $data['order_id'])->update([
                'total_amount' => (float) $data['total_amount'],
                'sale_tax_amount' => $salesTax,
                'final_total_amount' => $finalTotal,
            ]);
            if (! $orderTotalUpdate) {
                return Utility::apiError('Failed to update order total.', [], 221);
            }

            // Get branch id for the particular order
            $getOrder = Order::where('id', $data['order_id'])->first();

            $dateStr = Carbon::now()->format('dmY');
            $partialCount = PartialOrder::where('branch_id', $branchId)
                ->whereDate('created_at', Carbon::today())
                ->count();
            $uniquePartialNo = $branchInitials.'/'.$dateStr.'/Part-'.($partialCount + 1);

            // create new partial placeholder
            $partial = PartialOrder::create([
                'unique_partial_order_no' => $uniquePartialNo,
                'unique_order_no' => $data['unique_order_no'] ?? null,
                'unique_quotation_no' => $data['unique_quotation_no'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'order_id' => $data['order_id'],
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
                'notification_id' => null,
                'owner_id' => $data['owner_id'] ?? null,
                'quotation_type_id' => $data['quotation_type_id'] ?? null,
                'payment_term_condition' => $data['payment_term_condition'] ?? null,
                'date' => Carbon::now()->toDateString(),
                'enq_ref' => $data['enq_ref'] ?? null,
                'prepard_by' => $data['prepard_by'] ?? null,
                'branch_id' => $getOrder?->branch_id ?? null,
                'pdf_name' => $pdfFilePath ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'tin_number' => '27700707469',
                'user_id' => $adminUserId,
                'total_amount' => $data['total_amount'] ?? null,
                'customer_order_no' => $data['customer_order_no'] ?? null,
                'extra_notes' => $data['extra_notes'] ?? null,
                'courier_id' => $data['courier_id'] ?? null,
            ]);
            if (! $partial) {
                return Utility::apiError('Failed to create partial order.', [], 221);
            }

            $partialId = $partial?->id;
            $productListRows = [];
            $orderDetailsToUpdate = [];
            $grandTotal = 0;
            $calculations = [];

            // Build product rows
            foreach ($data['product_list'] as $item) {
                $price = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 0;
                $discount = $item['discount'] ?? 0;
                $igst = $item['igst'] ?? 0;
                $sendQty = $item['send_qty'] ?? 0;

                $baseAmount = $price * $quantity;
                $discountAmount = ($baseAmount * $discount) / 100;
                $afterDiscount = $baseAmount - $discountAmount;
                $gstAmount = ($afterDiscount * $igst) / 100;
                $totalAmount = $afterDiscount + $gstAmount;

                // FIX: correct null-coalescing precedence and ensure both values exist
                $balanceQtyFromItem = isset($item['balance_quantity']) ? (int) $item['balance_quantity'] : 0;
                $sendQtyFromItem = isset($item['send_qty']) ? (int) $item['send_qty'] : 0;
                $totalQty = $balanceQtyFromItem + $sendQtyFromItem;
                if ($balanceQtyFromItem == 0) {
                    $balanceQty = max(0, $quantity - $sendQtyFromItem);
                } else {
                    $balanceQty = max(0, $balanceQtyFromItem - $sendQtyFromItem);
                }

                $calculations[] = [
                    'base_amount' => $baseAmount,
                    'discount_amount' => $discountAmount,
                    'net_price' => $afterDiscount,
                    'gst_amount' => $gstAmount,
                    'total' => $totalAmount,
                ];
                $grandTotal += $totalAmount;

                $productListRows[] = [
                    'partial_order_id' => $partialId,
                    'order_id' => $data['order_id'],
                    'quotation_id' => $data['quotation_id'] ?? null,
                    'unique_order_no' => $data['unique_order_no'] ?? null,
                    'unique_quotation_no' => $data['unique_quotation_no'] ?? null,
                    'unique_partial_order_no' => $uniquePartialNo,
                    'product_id' => $item['product_id'] ?? $data['product_id'] ?? 0,
                    'principal_id' => $item['principal_id'] ?? $data['principal_id'] ?? null,
                    'part_no' => $item['part_no'] ?? '',
                    'description' => $item['description'] ?? '',
                    'hsn_code' => $item['hsn_code'] ?? '',
                    'in_stock' => $item['in_stock'] ?? 0,
                    'price' => $price,
                    'discount' => $discount,
                    'net_price' => $afterDiscount,
                    'igst' => $igst,
                    'balance_quantity' => $balanceQty,
                    'send_qty' => $sendQty,
                    'order_type' => 1,
                    'quantity' => $quantity,
                    'total' => $totalAmount,
                    'status' => 0,
                    'is_checked' => $item['is_checked'] ?? 1,
                    'partial_order_status' => $balanceQty == 0 ? 1 : 0,
                    'notes' => $item['notes'] ?? null,
                    'product_specification' => $item['product_specification'] ?? null,
                    'delivery_date_id' => $item['delivery_date_id'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (! empty($item['id'])) {
                    $orderDetailsToUpdate[] = [
                        'id' => $item['id'],
                        'balance_quantity' => $balanceQty,
                    ];
                }
            }

            // Insert new partial order detail rows in bulk (if any)
            if (! empty($productListRows)) {
                $inserted = PartialOrderDetails::insert($productListRows);
                if (! $inserted) {
                    return Utility::apiError('Failed to create partial order details.', [], 221);
                }
            }

            // Update corresponding OrderDetails rows' balance_quantity
            foreach ($orderDetailsToUpdate as $upd) {
                $updateStatus = OrderDetails::where('order_id', $data['order_id'])
                    ->where('id', $upd['id'])
                    ->update(['balance_quantity' => $upd['balance_quantity']]);
                if ($updateStatus === 0) {
                    Log::warning("OrderDetails update affected 0 rows for id: {$upd['id']}");
                }
            }

            // Mark order lines with balance_quantity <= 0 as partial_order_status = 1
            OrderDetails::where('order_id', $data['order_id'])
                ->where('balance_quantity', '<=', 0)
                ->update(['partial_order_status' => 1]);

            PartialOrder::where('id', $partialId)->update(['partial_order_status' => 1]);

            if (! empty($data['unique_order_no'])) {
                $orderCloseUpdate = Order::where(['id' => $data['order_id'], 'unique_order_no' => $data['unique_order_no']])
                    ->update([
                        'customer_order_no' => $data['customer_order_no'],
                        'is_order_closed' => '1',
                        'is_shipment_pending' => '0',
                        // 'pdf_name' => $pdfFilePath,
                    ]);
                // if (! $orderCloseUpdate) {
                //     return Utility::apiError('Failed to update order status.', [], 221);
                // }

            }
            if (! empty($data['order_id']) && empty($data['unique_order_no'])) {
                Order::where('id', $data['order_id'])->update([
                    'customer_order_no' => $data['customer_order_no'],
                    'is_shipment_pending' => '1',
                    // 'pdf_name' => $pdfFilePath,
                ]);
            }
            $responsePayload = array_merge($data, [
                'product_list' => $productListRows,
                'multiProdCal' => [
                    'calculations' => $calculations,
                    'grand_total' => $grandTotal,
                ],
                'totalcalc' => $grandTotal,
            ]);

            // ProcessPartialOrder::dispatch($responsePayload)
            // ->onQueue('order_pdf')
            // ->delay(0);

            // return message depends on whether it was update or create
            $message = ! empty($data['partial_order_id']) ? 'updated successfully.' : 'generated successfully.';

            return Utility::apiSuccess($message, $responsePayload, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Fail to generate order', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getPartialOrder(Request $request)
    {
        try {
            // Collect only the relevant inputs
            $data = $request->only([
                'per_page',
                'branch_list',
                'owner_list',
                'branch_list',
                'currency_list',
                'principal_list',
                'start_date',
                'end_date',
                'search',
                'download',
            ]);

            $perPage = $data['per_page'] ?? config('constant.per_page', 15);

            if (! empty($data['download'])) {
                $columns = [
                    'unique_partial_order_no' => 'Partial Order No',
                    'unique_order_no' => 'Order No',
                    'unique_quotation_no' => 'Quotation No',
                    'date' => 'Date',
                    'branch' => 'Branch',
                    'owner' => 'Owner',
                    'currency' => 'Currency',
                    'company' => 'Company',
                    'total_amount' => 'Total Amount',
                    'customer_order_no' => 'Customer Order No',
                    'courier' => 'Courier',
                ];

                $filename = 'partial_order_'.now()->format('Ymd_His').'.xlsx';
                (new PartialOrderExport($data, $columns))->queue("exports/{$filename}", 'public');

                $fileUrl = url("storage/exports/{$filename}");

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => $fileUrl,
                ]);
            }

            $canView = Utility::checkViewPermission('partial_order');
            $canBranch = Utility::checkBranchesViewPermission('partial_order');

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
                'invoice_id',
                'user_id',
            ])
                ->with([
                    'orderDetails',
                    'companyDetails:id,company_name,email_id',
                    'branchDetails:id,name',
                    'currencyDetails:id,code',
                    'pendingQuotationDetails:unique_quotation_no,quotation_id,reason,status_code,follow_up_date,total_amount,reason_status_id,last_updated_at',
                ])
                ->whereNull('deleted_at')
                ->when(
                    ! empty($data['branch_list']),
                    fn ($q) => $q->where('branch_id', $data['branch_list'])
                )
                ->when(
                    ! empty($data['owner_list']),
                    fn ($q) => $q->where('owner_id', $data['owner_list'])
                )
                ->when(
                    ! empty($data['currency_list']),
                    fn ($q) => $q->where('currency_id', $data['currency_list'])
                )
                ->when(
                    ! empty($data['principal_list']),
                    fn ($q) => $q->whereHas('orderDetails', function ($d) use ($data) {
                        $d->where('principal_id', (array) $data['principal_list']);
                    })
                )
                ->when(! empty($data['start_date']) && ! empty($data['end_date']), function ($q) use ($data) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($data['start_date'])->startOfDay(),
                        Carbon::parse($data['end_date'])->endOfDay(),
                    ]);
                })->when(
                    $canView || $canBranch,
                    function ($q) use ($canView, $canBranch) {
                        $q->where(function ($q) use ($canView, $canBranch) {

                            if ($canView) {
                                $q->orWhere('user_id', Auth::id());
                            }

                            if ($canBranch) {
                                $q->orWhere('branch_id', Auth::user()->branch_id);
                            }

                        });
                    }
                )

                ->when(! empty($data['search']), function ($q) use ($data) {
                    $term = $data['search'];
                    $q->where(function ($sub) use ($term) {
                        $sub->where('unique_quotation_no', 'like', "%{$term}%")
                            ->orWhere('unique_order_no', 'like', "%{$term}%")
                            ->orWhere('unique_partial_order_no', 'like', "%{$term}%")
                            ->orWhere('customer_order_no', 'like', "%{$term}%")

                            ->orWhereHas(
                                'companyDetails',
                                fn ($c) => $c->where('customer_name', 'like', "%{$term}%")
                            )
                            ->orWhereHas(
                                'orderDetails',
                                fn ($d) => $d->where('part_no', 'like', "%{$term}%")
                                    ->orWhere('hsn_code', 'like', "%{$term}%")
                                    ->orWhere('description', 'like', "%{$term}%")
                                    ->orWhere('in_stock', 'like', "%{$term}%")
                                    ->orWhere('send_qty', 'like', "%{$term}%")
                                    ->orWhere('balance_quantity', 'like', "%{$term}%")
                                    ->orWhere('total', 'like', "%{$term}%")
                                    ->orWhere('quantity', 'like', "%{$term}%")
                                    ->orWhere('net_price', 'like', "%{$term}%")
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
