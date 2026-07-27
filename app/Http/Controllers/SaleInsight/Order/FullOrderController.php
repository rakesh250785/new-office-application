<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Exports\OrderExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Jobs\CloseOrder;
use App\Jobs\ProcessOrder;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PendingQuotation;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\QuotationFormat;
use App\Models\QuotationType;
use App\Models\States;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Log;
use View;

class FullOrderController extends Controller
{
    public function __construct() {}

    public function storeOrder(Request $request)
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
                'unique_quotation_no',
                'customer_order_no',
                'overdues_value',
                'overdue_no',
                'courier_id',
                'submit_type',
                'state',
                'country',
                'quotation_type',
                'company_details',
                'delivery_term_data',
                'delivery_date_custom',
                'courier_name',
                'shipping_state_name',
            ]);

            // return $data;

            // Validation rule
            $validator = Validator::make($data, [
                'courier_id' => 'required|integer|exists:couriers,id',
                'customer_order_no' => 'required|string',
                'overdues_value' => 'sometimes|nullable',
                'overdue_no' => 'sometimes|nullable',
                'unique_quotation_no' => 'required|string',
                'product_id' => 'nullable|integer|exists:products,id',
                'product_description' => 'sometimes|nullable',
                'principal_type' => 'nullable|string',
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
                'quotation_id' => 'nullable|integer|exists:quotations,id',
                'product_list' => 'required|array|min:1',
                'product_list.*.part_no' => 'required|string',
                'product_list.*.description' => 'required|string|max:1000',
                'product_list.*.hsn_code' => 'required|string',
                'product_list.*.quantity' => 'required|numeric|min:1',
                'product_list.*.in_stock' => 'nullable|numeric',
                'product_list.*.price' => 'required|numeric|min:0',
                'product_list.*.discount' => 'nullable|numeric|min:0|max:100',
                'product_list.*.net_price' => 'required|numeric|min:0',
                'product_list.*.igst' => 'nullable|numeric|min:0',
                'product_list.*.total' => 'required|numeric|min:0',
                'product_list.*.notes' => 'nullable|string|max:1000',
                'product_list.*.product_specification' => 'nullable|string',
                'total_amount' => 'required|numeric|min:0',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            if ($data['submit_type'] == 'order_preview') {
                return Utility::apiSuccess('order_preview', [], 200);
            }

            // Check if quotation already exist
            $checkExistingQuotationInfo = Order::where(['quotation_id' => $data['quotation_id'], 'unique_quotation_no' => $data['unique_quotation_no']])->first();

            if ($checkExistingQuotationInfo) {
                return Utility::apiError('Already order generated against this quotaion number', [], 221);
            }

            // Auth info
            $adminId = Auth::id();
            $branchId = Auth::user()->branch_id;
            $branchName = Branch::find($branchId)->name;
            $orderDate = Carbon::now()->format('Y-m-d 00:00:00');

            // Customer and currency info
            $customerInfo = Customer::find($data['company_id']);
            $currencyInfo = Currency::find($data['currency_id']);

            // Get unique order number
            $orderNumber = $this->generateOrderNumber($branchName, $branchId, $orderDate);

            // PDF path
            $pdfFilePath = now()->year.'/order_'.time().'_'.date('dmy').'.pdf';

            // Prepare order data
            $orderData = [
                'unique_quotation_no' => $data['unique_quotation_no'],
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
                'date' => $data['date'] ?? null,
                'enq_ref' => $data['enq_ref'] ?? null,
                'prepard_by' => $data['prepard_by'] ?? null,
                'branch_id' => $branchId ?? null,
                'pdf_name' => $pdfFilePath ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'tin_number' => '27700707469',
                'user_id' => $adminId,
                'total_amount' => $data['total_amount'] ?? null,
                'customer_order_no' => $data['customer_order_no'] ?? null,
                'overdues_value' => $data['overdues_value'] ?? null,
                'overdue_no' => $data['overdue_no'] ?? null,
                'is_order_closed' => '0',
                'courier_id' => $data['courier_id'] ?? null,
                'is_shipment_pending' => 1,
                'pdf_status' => 'processing',
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
            $order = Order::create($orderData);

            // Return if fail
            if (! $order) {
                return Utility::apiError('Failed to save order', [], 221);
            }

            // Initialize variable
            $orderId = $order->id;
            $grandTotal = 0;
            $subUnitTotal = 0;
            $subNetTotal = 0;
            $totalIgstTotal = 0;
            $productList = [];

            // Sync order details
            if (OrderDetails::where('order_id', $orderId)->exists()) {
                if (OrderDetails::where('order_id', $orderId)->delete() === false) {
                    return Utility::apiError('Failed to delete existing order details', [], 221);
                }
            }

            // Product calculation

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
                    'order_id' => $orderId,
                    'user_id' => $adminId,
                    'quotation_id' => $data['quotation_id'],
                    'unique_order_no' => $orderNumber,
                    'unique_quotation_no' => $data['unique_quotation_no'],
                    'product_id' => $item['product_id'] ?? 0,
                    'principal_id' => $item['principal_id'] ?? null,
                    'part_no' => $item['part_no'] ?? '',

                    'description' => $item['description'] ?? '', // Editable
                    'principal' => $item['principal']['type'] ?? $item['principal'] ?? null, // Editable
                    'heading' => $item['heading'] ?? '', // Editable
                    'specification' => $item['specification'] ?? '', // Editable
                    'notes' => $item['notes'] ?? null, // Editable
                    'product_specification' => $item['product_specification'] ?? null, // Editable

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
                    'delivery_date_id' => $item['delivery_date_id'] ?? 0,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert order details
            $orderDetailInsert = OrderDetails::insert($productList);

            // Return if fail
            if (! $orderDetailInsert) {
                return Utility::apiError('Fail to insert updated order details', [], 221);
            }

            // Refresh quotation product details
            $quotationDetailDelete = QuotationDetail::where('quotation_id', $data['quotation_id'])->delete();

            // Return if fail
            if (! $quotationDetailDelete) {
                return Utility::apiError('Fail to delete existing quotation details', [], 221);
            }

            // Insert quotation details
            $insertStatus = QuotationDetail::insert($productList);

            // Return if fail
            if (! $insertStatus) {
                return Utility::apiError('Fail to delete quotation details', [], 221);
            }

            // Update quotation flags
            $quotationFilter = [
                'id' => $data['quotation_id'],
                'company_id' => $data['company_id'],
            ];

            // Update quotation status
            $updateQuotationStatus = Quotation::where($quotationFilter)->first();
            if ($updateQuotationStatus) {
                $updateQuotationStatus->is_order_pending = '0';

                $updateQuotationStatus->save();
            }

            // Return if fail
            if (! $updateQuotationStatus) {
                return Utility::apiError('Fail to update quotation status', [], 221);
            }

            // Mark pending quotation deleted
            $pendingFilter = ['unique_quotation_no' => $data['unique_quotation_no'], 'quotation_id' => $data['quotation_id']];

            $updatePendingQuotation = PendingQuotation::where($pendingFilter)->update(['status_code' => 'win', 'reason_status_id' => 1]);

            // Return if fail
            if (! $updatePendingQuotation) {
                return Utility::apiError('Fail to update pending quotation', [], 221);
            }

            // Get pdf info
            $customerInfo = $customerInfo?->toArray();
            $states = States::where('id', $customerInfo['state_id'] ?? null)->first();
            $branchAddress = QuotationFormat::where('branch_id', $branchId)->whereNull('deleted_at')->value('billing_address');
            $quotationType = QuotationType::where('id', $data['quotation_type_id'])->first();
            $pdfRec = [
                'term_conditon_bg_img' => url('appLogo/bannerImg2.png'),
                'pdf_name' => $pdfFilePath,
                'old_pdf_name' => $checkExistingQuotationInfo?->pdf_name,
                'prepared_by' => $data['prepard_by'],
                'delivery_term_data' => $data['delivery_term_data'] ?? $data['delivery_date_custom'] ?? null,
                'courier_name' => $data['courier_name'],
                'quotationInfo' => [
                    'id' => $data['quotation_id'],
                    'unique_quotation_no' => $data['unique_quotation_no'],
                    'date' => $updateQuotationStatus->date,
                    'user_id' => $adminId,
                    'branch_id' => $branchId,
                    'quotation_type' => $quotationType?->type,
                ],
                'orderInfo' => [
                    'id' => $orderId,
                    'user_id' => $adminId,
                    'branch_id' => $branchId,
                    'customer_order_no' => $data['customer_order_no'],
                    'unique_order_no' => $orderNumber,
                    'unique_quotation_no' => $data['unique_quotation_no'],
                    'order_date' => $data['date'],
                    'date' => Carbon::now()->format('d-m-Y'),
                    'ref' => $data['enq_ref'],
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

                'billing' => [
                    'company' => $customerInfo['company_name'] ?? null,
                    'address' => $data['billing_address'],
                    'email' => $data['billing_email'],
                    'landline' => $data['billing_landline'],
                    'mobile' => $data['billing_mobile'],
                    'gstn' => $customerInfo['gst_number'] ?? null,
                    'city' => $data['billing_city'],
                    'pincode' => $data['billing_pin_code'],
                    'state' => $states->name ?? $customerInfo['other_state'] ?? null,
                    'contact_person' => $data['contact_person'],
                    'country' => $data['company_details']['country']['name'] ?? null,
                ],

                'shipping' => [
                    'company' => $customerInfo['company_name'] ?? null,
                    'address' => $data['shipping_address'],
                    'email' => $data['shipping_email'],
                    'landline' => $data['shipping_landline'],
                    'mobile' => $data['shipping_mobile'],
                    'gstn' => $customerInfo['gst_number'] ?? null,
                    'city' => $data['shipping_city'],
                    'pincode' => $data['shipping_pin_code'],
                    'state' => $data['shipping_state_name'] ?? $customerInfo['other_state'] ?? null,
                    'contact_person' => $data['contact_person'],
                    'country' => $data['company_details']['country']['name'] ?? null,
                ],
                'items' => $productList,
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
            ];

            ProcessOrder::dispatch($pdfRec)
            // ->onQueue('order_pdf')
                ->delay(0);

            // Return response
            return Utility::apiSuccess('generated successfully', $pdfRec, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Fail to generate order', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getOrder(Request $request)
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
                'page',
            ]);

            $perPage = $data['per_page'] ?? config('constant.per_page', 15);

            if (! empty($data['download'])) {
                $columns = [
                    'unique_order_no' => 'Order No',
                    'unique_quotation_no' => 'Quotation No',
                    'part_no' => 'Part NO.',
                    'quantity' => 'Quantity',
                    'description' => 'Description',
                    'date' => 'Date',
                    'branch' => 'Branch',
                    'owner' => 'Owner',
                    'currency' => 'Currency',
                    'company' => 'Company',
                    'total_amount' => 'Total Amount',
                    'customer_order_no' => 'Customer Order No',
                    'courier' => 'Courier',
                    'price' => 'Price',
                    'net_price' => 'Net Price',
                    'discount' => 'Discount',
                    'igst' => 'IGST',
                    'is_order_closed' => 'Status',
                ];

                $filename = 'order_'.now()->format('Ymd_His').'.xlsx';
                (new OrderExport($data, $columns))->queue("exports/{$filename}", 'public');

                $fileUrl = url("storage/exports/{$filename}");

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => $fileUrl,
                ]);
            }

            $query = Order::select([
                'id',
                'unique_order_no',
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
                // 'notification_id',
                'quotation_type_id',
                'payment_term_condition',
                'date',
                'prepard_by',
                'pdf_name',
                'pdf_status',
                'enq_ref',
                'delivery_date_id',
                'customer_order_no',
                'overdues_value',
                'overdue_no',
                'courier_id',
                'is_order_closed',
                'user_id',
            ])
                ->with([
                    'orderDetails',
                    'orderDetails.uom:id,uom',
                    'orderDetails.principal:id,type',
                    'companyDetails:id,company_name,customer_name,email_id',
                    'branchDetails:id,name',
                    'currencyDetails:id,code',
                    'ownerDetails:id,name',
                    'pendingQuotationDetails:order_id,unique_quotation_no,quotation_id,reason,status_code,follow_up_date,total_amount,reason_status_id,last_updated_at',
                ])
                ->whereNull('deleted_at');

            if (! empty($data['branch_list'])) {
                $query->where('branch_id', (array) $data['branch_list']);
            }

            if (! empty($data['owner_list'])) {
                $query->where('owner_id', (array) $data['owner_list']);
            }

            if (! empty($data['currency_list'])) {
                $query->where('currency_id', (array) $data['currency_list']);
            }

            if (! empty($data['status_list'])) {
                // filter on is_order_pending (kept consistent with getQuotation)
                $query->where('is_order_closed', (array) $data['status_list']);
            }

            if (! empty($data['principal_list'])) {
                $query->whereHas('orderDetails', function ($d) use ($data) {
                    $d->where('principal_id', (array) $data['principal_list']);
                });
            }

            // Date filter (start_date & end_date) - same style as getQuotation
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            if (
                Utility::checkViewPermission('order') ||
                Utility::checkBranchesViewPermission('order')
            ) {

                $query->where(function ($q) {

                    if (Utility::checkViewPermission('order')) {
                        $q->orWhere('user_id', Auth::id());
                    }

                    if (Utility::checkBranchesViewPermission('order')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }
                });
            }

            // Search filter - mirror getQuotation searching behaviour
            if (! empty($data['search'])) {
                $term = $data['search'];
                $query->where(function ($sub) use ($term) {
                    $sub->where('unique_order_no', 'like', "%{$term}%")
                        ->orWhere('unique_quotation_no', 'like', "%{$term}%")
                        ->orWhere('customer_order_no', 'like', "%{$term}%")
                        ->orWhere('lead_from', 'like', "%{$term}%")
                        ->orWhereHas('ownerDetails', function ($o) use ($term) {
                            $o->where('name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('currencyDetails', function ($c) use ($term) {
                            $c->where('code', 'like', "%{$term}%");
                        })
                        ->orWhereHas('companyDetails', function ($c) use ($term) {
                            $c->where('company_name', 'like', "%{$term}%")
                                ->orWhere('customer_name', 'like', "%{$term}%");
                        })
                        ->orWhereHas('orderDetails', function ($d) use ($term) {
                            $d->where('part_no', 'like', "%{$term}%")
                                ->orWhereHas('principal', function ($p) use ($term) {
                                    $p->where('type', 'like', "%{$term}%");
                                });
                        });
                });
            }

            $query->orderByDesc('id');

            $orderData = $query->paginate($perPage);

            return Utility::apiSuccess('list_order', $orderData, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed getOrder server error', ['exception' => $ex->getMessage()], 500);
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
            if (! empty($update_pending_infog)) {
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
            $prefix = ucfirst(substr(trim($branchName), 0, 3));
            $date = Carbon::parse($quotationDate)->startOfDay();
            $formattedDate = $date->format('Ymd');

            $latestOrder = Order::where('branch_id', $branchId)
                ->whereNull('deleted_at')
                ->whereDate('created_at', $date)
                ->orderByDesc('id')
                ->first();

            $sequence = 1;

            if ($latestOrder && ! empty($latestOrder->unique_order_no)) {
                if (preg_match('/order-(\d+)$/', $latestOrder->unique_order_no, $matches)) {
                    $sequence = (int) $matches[1] + 1;
                }
            }

            do {
                $orderNumber = "{$prefix}/{$formattedDate}/order-{$sequence}";
                $exists = Order::where('unique_order_no', $orderNumber)
                    ->whereNull('deleted_at')
                    ->exists();

                $sequence++;
            } while ($exists);

            return $orderNumber;
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Failed generate order number', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteOrder(Request $request)
    {
        try {

            $data = $request->only([
                'id',
                'unique_quotation_no',
            ]);

            $validator = Validator::make($data, [
                'id' => 'required',
                'unique_quotation_no' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            $records = Order::where('id', $data['id'])->delete();
            $quotation = Quotation::where('unique_quotation_no', $data['unique_quotation_no'])->first();

            if (! $records) {
                return Utility::apiError('Order not found', [], 221);
            }
            OrderDetails::where('order_id', $data['id'])->delete();
            Quotation::where('id', $quotation?->id)->where('unique_quotation_no', $data['unique_quotation_no'])->update(['is_order_pending' => true]);
            PendingQuotation::where('quotation_id', $quotation?->id)->update(
                [
                    'reason' => 'Open',
                    'status_code' => 'open',
                    'last_updated_at' => Carbon::now(),
                    'user_id' => Auth::id(),
                    'follow_up_date' => Carbon::now(),
                    'branch_id' => Auth::user()->branch_id ?? null,
                    'reason_status_id' => 3,
                ]
            );

            return Utility::apiSuccess('deleted successfully !', [], 200);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function closeOrder(Request $request)
    {
        try {
            // Extract only required fields
            $data = $request->only(['order_id']);

            // Validate request
            $validator = Validator::make($data, [
                'order_id' => 'required|integer|exists:orders,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            // Fetch order info
            $order = Order::with('customer', 'orderDetails', 'branchAddress')
                ->where('id', $data['order_id'])
                ->first();

            // Return if fail
            if (! $order) {
                return Utility::apiError('Order not found', [], 221);
            }

            // Prepare data
            $response = [
                'order_info' => $order,
                'order_details' => $order->orderDetails,
                'customer_info' => $order->customer,
                'billing_address_info' => $order->branchAddress,
            ];

            // Render the view as string for frontend preview if needed
            $htmlPreview = View::make('office.order.preview_close_order', $response)->render();

            // Return response
            return Utility::apiSuccess('Order preview data fetched successfully', [
                'preview_html' => $htmlPreview,
                'raw_data' => $response,
            ]);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Server error while closing order', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function closeOrderId(Request $request)
    {
        try {
            // Get specific fields
            $data = $request->only(['order_id']);

            // Validate request
            $validator = Validator::make($data, [
                'order_id' => 'required|integer|exists:orders,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            // Ordero id
            $orderId = $data['order_id'];

            // Check if order already fully completed
            if ($this->checkPartialOrderStatus($orderId)) {
                return Utility::apiError('Order has been fully completed.', [], 200);
            }

            // Fetch order info
            $orderInfo = optional(Order::where('id', $orderId)
                ->when(! Auth::user()->hasPermission('branch_all'), fn ($q) => $q->where('branch_id', Auth::user()->branch_id))
                ->whereNull('deleted_at')
                ->first())->toArray() ?: false;

            // Return if not match
            if (! is_array($orderInfo)) {
                return Utility::apiError('Please generate an order first.', [], 221);
            }

            // Get meta info
            $branchId = Auth::user()->branch_id;
            $adminUserId = Auth::user()->id;
            $customerId = $orderInfo['customer_id'];

            // Get order details
            $orderDetails = OrderDetails::where(['order_id' => $orderId])->whereNull('deleted_at')->get();

            // Return if not found
            if (! $orderDetails) {
                return Utility::apiError('Order detail not found', [], 221);
            }

            // Get branch name
            $branchMap = Branch::where('id', $branchId)->first();
            if (! $branchMap) {
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
            if (! $insertedOrderId) {
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
                    'dt_created' => now(),
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
            $pdfFilePath = now()->year.'/order_'.time().'_'.date('dmy').'.pdf';

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
            \Log::error('closeOrderId error: '.$e->getMessage());

            return Utility::apiError('Something went wrong.');
        }
    }

    public function addOrderReason(Request $request)
    {
        try {

            $data = $request->only([
                'order_id',
                'quotation_id',
                'order_number',
                'order_value',
                'order_date',
                'customer_id',
                'quotation_text',
                'reason_status_id',
                'reason_status',
                'quotation_status_code',
                'unique_quotation_no',
                'reason_text',
                'total_amount',
            ]);

            $validator = Validator::make($data, [
                'order_id' => 'required',
                'quotation_id' => 'required|integer|exists:quotations,id',
                'order_number' => 'required|string',
                'order_value' => 'required|numeric',
                'order_date' => 'required|date',
                'quotation_status_code' => 'sometimes|nullable',
                'unique_quotation_no' => 'required',
                'reason_text' => 'required',
                'reason_status_id' => 'required',
                'total_amount' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            $admin = Auth::user();

            $match = [
                'order_id' => $data['order_id'],
                'quotation_id' => $data['quotation_id'],
                'unique_quotation_no' => $data['unique_quotation_no'],
                'unique_order_no' => $data['order_number'],
            ];

            $existing = PendingQuotation::where($match)->first();

            $mergedReason = $data['reason_text'];
            if ($existing) {
                $mergedReason = $existing->reason
                ? $existing->reason.', '.$data['reason_text']
                : $data['reason_text'];
            }

            /** ✍️ values */
            $values = [
                'amount' => $data['order_value'],
                'date' => Carbon::parse($data['order_date']),
                'reason' => $mergedReason,
                'total_amount' => $data['total_amount'],
                'reason_status_id' => $data['reason_status_id'],
                'branch_id' => $admin['branch_id'] ?? null,
                'user_id' => $admin['id'] ?? null,
                'status_code' => $data['quotation_status_code'] ?? null,
                'last_updated_at' => now(),
                'follow_up_date' => now(),
            ];

            $reason = PendingQuotation::updateOrCreate($match, $values);

            if (! $reason) {
                return Utility::apiError('fail_add_reason', [], 221);
            }

            return Utility::apiSuccess('reason added successfully', [], 200);

        } catch (Exception $ex) {

            Log::error($ex);

            return Utility::apiError(
                'Server error while adding reason',
                ['exception' => $ex->getMessage()],
                500
            );
        }
    }

    public function checkPartialOrderStatus($orderId)
    {
        try {
            // Get auth info
            $user = Auth::user();
            $conditions = ['id' => $orderId, 'deleted_at' => null];

            // Check permission
            if (! $user->hasPermission('branch_all')) {
                $conditions['branch_id'] = $user->branch_id;
            }

            // Check order status
            $hasAny = OrderDetails::where($conditions)->exists();
            $hasPending = OrderDetails::where($conditions)->where('partial_order_status', 0)->exists();

            // Return response
            return $hasAny ? ! $hasPending : false;
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Server error while getting checkPartialOrderStatus', ['exception' => $ex->getMessage()], 500);
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
            $lastQuote = Quotation::whereNull('deleted_at')
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

    public function orderPdfStatus(Request $request)
    {
        try {
            $data = $request->only([
                'order_id',
                'unique_order_no',
            ]);

            $validator = Validator::make($data, [
                'order_id' => 'required',
                'unique_order_no' => 'required',
            ]);

            if ($validator->fails()) {
                return Utility::apiError('Validation error', $validator->errors(), 221);
            }

            $q = Order::where('id', $data['order_id'] ?? null)
                ->where('unique_order_no', $data['unique_order_no'] ?? null)
                ->first();

            if ($q?->pdf_status == 'ready' && $q?->pdf_name) {
                return Utility::apiSuccess('Order pdf status', [
                    'status' => 'ready',
                    'url' => asset('storage/'.$q->pdf_name),
                ], 200);

            } elseif ($q?->pdf_status == 'processing') {
                return Utility::apiSuccess('Order pdf status', [
                    'status' => 'processing',
                    'message' => 'PDF is being Processing',
                ], 200);
            } else {
                return Utility::apiSuccess('Order pdf status', [
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
