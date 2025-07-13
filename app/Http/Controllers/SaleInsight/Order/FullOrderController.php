<?php

namespace App\Http\Controllers\Office;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Quotation;
use Illuminate\Support\Facades\Validator;
use App\Jobs\Order as Orders;
use Illuminate\Support\Facades\Auth;
use App\Models\QoutationDetails;
use App\Models\PendingQuotation;
use App\Models\PartialOrder;
use App\Models\PartialOrderDetails;
use Illuminate\Http\Request;
use App\Models\QuatationAdd;
use App\Models\OrderDetails;
use App\Models\Customer;
use App\Jobs\CloseOrder;
use Exception, Log;
use App\Models\Order;
use App\Models\Courier;
use Carbon\Carbon;
use DataTables;
use Response;
use Config;
use PDF;
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
                'quotation_id' => 'required',
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
            return Utility::apiSuccess('Order data fetched successfully.', [
                'data' => $data,
            ]);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed fetching getQuotationOrder server error', ['exception' => $ex->getMessage()]);
        }
    }

    public function storeUpdatedOrder(Request $request)
    {
        try {
            $data = $request->validate([
                'quotation_info.in_quot_num' => 'required|string',
                'quotation_info.in_quot_id' => 'required|integer|exists:quotations,id',
                'quotation_info.*' => 'nullable',
                'customer_info.cust_id' => 'required|integer|exists:customers,in_cust_id',
                'customer_info.*' => 'nullable',
                'sel_prods_details' => 'required|array|min:1',
                'sel_prods_details.*.in_product_id' => 'required|integer',
                'sel_prods_details.*.in_pro_qty' => 'required|numeric',
                'sel_prods_details.*.*' => 'nullable',
            ]);

            $adminId = auth()->id();
            $branchId = auth()->user()->branch_id;
            $branchName = config('constant.branch_wise')[$branchId];
            $quotationDate = now()->format('Y-m-d');

            $quotationInfo = $data['quotation_info'];
            $customerInfo = $data['customer_info'];
            $products = $data['sel_prods_details'];

            $orderNumber = $this->generateOrderNumber($branchName, $branchId, $quotationDate);
            $pdfFile = 'order_' . time() . '_' . date('dmy') . '.pdf';

            $order = Order::create([
                'in_uniq_order_id' => $orderNumber,
                'in_qoute_uniqu_id' => $quotationInfo['in_quot_num'],
                'in_cust_id' => $customerInfo['cust_id'],
                'dt_cust_order_date' => now(),
                'flt_ord_net_total' => $quotationInfo['fl_nego_amt'] ?? 0,
                'flt_ord_total' => $quotationInfo['fl_nego_amt'] ?? 0,
                'st_currency_applied' => $quotationInfo['currency'] ?? '',
                'log_in_id' => $adminId,
                'stn_pdf_name' => $pdfFile,
                'quotation_type' => $products[0]['quotation_type'] ?? '',
                'tnc' => $products[0]['term_condition'] ?? '',
                'in_branch_id' => $branchId,
                'dt_created' => now(),
                'dt_modify' => now(),
            ]);

            Customer::where('in_cust_id', $customerInfo['cust_id'])->update([
                'st_com_address' => $customerInfo['auto_pop_addr'] ?? '',
                'st_cust_city' => $customerInfo['auto_pop_city'] ?? '',
                'st_con_person1' => $customerInfo['auto_pop_cust_name'] ?? '',
                'in_pincode' => $customerInfo['auto_pop_pincod'] ?? '',
                'st_cust_state' => $customerInfo['auto_pop_state'] ?? '',
                'st_cust_mobile' => $customerInfo['auto_pop_phone'] ?? '',
                'st_cust_email' => $customerInfo['auto_pop_email'] ?? '',
            ]);

            foreach ($products as $prod) {
                $order->details()->create([
                    'in_ord_prod_id' => $prod['in_product_id'],
                    'in_ord_pro_qty' => $prod['in_pro_qty'],
                    'flt_ord_pro_price' => $prod['fl_pro_unitprice'],
                    'flt_ord_pro_disct' => $prod['fl_discount'],
                    'flt_ord_pro_net_price' => $prod['fl_net_price'],
                    'flt_ord_pro_row_total' => $prod['fl_row_total'],
                    'in_ord_pro_bal_qty' => $prod['in_pro_qty'],
                    'in_ord_delivery_period' => $prod['in_pro_deli_period'],
                    'product_comments' => $prod['prod_comments'] ?? '',
                    'st_part_no' => $prod['st_part_no'],
                    'st_hsn_no' => $prod['stn_hsn_no'],
                    'in_igst_rate' => $prod['in_igst_rate'],
                    'quotation_type' => $prod['quotation_type'],
                    'term_condition' => $prod['term_condition'],
                    'uom' => $prod['uom'],
                    'moc' => $prod['moc'],
                    'specifications' => $prod['specifications'],
                    'prod_head' => $prod['prod_head'],
                    'in_ord_pro_status' => 0,
                    'flg_partord_status' => 0,
                ]);
            }

            QoutationDetails::where('in_quot_id', $quotationInfo['in_quot_id'])->delete();
            QoutationDetails::insert(array_map(function ($prod) use ($quotationInfo, $customerInfo) {
                return array_merge($prod, [
                    'in_quot_id' => $quotationInfo['in_quot_id'],
                    'in_cust_id' => $customerInfo['cust_id']
                ]);
            }, $products));

            QuatationAdd::where('in_quot_id', $quotationInfo['in_quot_id'])
                ->update(['is_order_pending' => 1]);

            dispatch(new Orders([
                'order_id' => $order->id,
                'email' => auth()->user()->email,
                'cc_email' => auth()->user()->cc_email,
                'file_path' => $pdfFile,
                'quot_type' => $products[0]['quotation_type'],
                'multiProdCal' => $this->calculateProductTotals($products),
                'totalcalc' => $this->calculateTotal($products),
            ]));

            return response()->json([
                'code' => 200,
                'message' => 'Order created successfully.',
                'order_id' => $order->id,
            ]);

        } catch (\Exception $e) {
            \Log::error("Order Create Error: " . $e->getMessage());
            return response()->json(['code' => 500, 'error' => 'Order creation failed.']);
        }
    }


    public function update_pending_order($id)
    {
        try {
            if (Auth::user()->hasPermission('branch_all')) {
                $update_pending_status = QuatationAdd::where('in_quot_num', $id)->update(['is_order_pending' => 1]);
            } else {
                $update_pending_status = QuatationAdd::where(['in_quot_num' => $id, 'in_branch_id' => Auth::user()->branch_id])->update(['is_order_pending' => 1]);
            }
            if (!empty($update_pending_status)) {
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
                ->where('deleted_at', 0)
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

    public function get_oeder_details($oeder_id)
    {
        try {
            $order_details = OrderDetails::where(['in_order_id' => $oeder_id])->where('flg_deleted', 0)->get();
            if (!empty($order_details)) {
                $order_details = $order_details->toArray();
                return $order_details;
            } else {
                $order_details = [];
            }
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function chek_partially_order_status($in_order_id)
    {
        try {

            if (Auth::user()->hasPermission('branch_all')) {
                $query = OrderDetails::where('in_order_id', $in_order_id)->where('flg_deleted', 0)->get();
            } else {
                $query = OrderDetails::where(['in_order_id' => $in_order_id, 'branch_id' => \Auth::user()->branch_id])->where('flg_deleted', 0)->get();
            }
            if (!empty($query)) {
                if (Auth::user()->hasPermission('branch_all')) {
                    $query1 = OrderDetails::where('in_order_id', $in_order_id)->where('flg_deleted', 0)->where('flg_partord_status', 0)->get();
                } else {
                    $query1 = OrderDetails::where(['in_order_id' => $in_order_id, 'branch_id' => \Auth::user()->branch_id])->where('flg_deleted', 0)->where('flg_partord_status', 0)->get();
                }
                if (!empty($query1)) {
                    return false;
                } elseif (empty($query1)) {
                    return true;
                } else {
                    return false;
                }
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
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


    public function update_order_detail_tbl($in_ord_detail_id, $update_array)
    {
        try {

            if (Auth::user()->hasPermission('branch_all')) {
                $orderDetails = OrderDetails::where('in_ord_detail_id', $in_ord_detail_id)->update($update_array);
            } else {
                $orderDetails = OrderDetails::where(['in_ord_detail_id' => $in_ord_detail_id, 'branch_id' => Auth::user()->branch_id])->update($update_array);
            }
            if ($orderDetails) {
                return true;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function isert_orders_details($insert_array, $in_ord_detail_id = "")
    {
        try {
            $insert = PartialOrderDetails::insert($insert_array);
            if ($in_ord_detail_id != "") {
                $updated_ord_pro_bal_qty = $insert_array['in_balance_pro_qty'];
                if ($updated_ord_pro_bal_qty < 0)
                    $updated_ord_pro_bal_qty = 0;
                $update_in_ord_pro_sent_qty = array(
                    'in_ord_pro_sent_qty' => 0,
                    'in_ord_pro_bal_qty' => $updated_ord_pro_bal_qty
                );
                $this->update_order_detail_tbl($in_ord_detail_id, $update_in_ord_pro_sent_qty);
            }
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function update_part_order_detail_status($in_order_id, $update_array)
    {
        try {
            if (Auth::user()->hasPermission('branch_all')) {
                $orderDetails = OrderDetails::where('in_order_id', $in_order_id)->where('in_order_id', $in_order_id)->where('in_ord_pro_bal_qty', '<=', 0)->update($update_array);

            } else {
                $orderDetails = OrderDetails::where(['in_order_id' => $in_order_id, 'branch_id' => Auth::user()->branch_id])->where('in_order_id', $in_order_id)->where('in_ord_pro_bal_qty', '<=', 0)->update($update_array);
            }
            $flt_ord_pro_row_total_array = array('flt_ord_pro_row_total' => 0);
            if (Auth::user()->hasPermission('branch_all')) {
                $orderDetails = OrderDetails::where('in_order_id', $in_order_id)->where('in_ord_pro_bal_qty', '>', 0)->update($flt_ord_pro_row_total_array);
            } else {
                $orderDetails = OrderDetails::where(['in_order_id' => $in_order_id, 'branch_id' => Auth::user()->branch_id])->where('in_ord_pro_bal_qty', '>', 0)->update($flt_ord_pro_row_total_array);
            }
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }
    public function update_order($in_order_id, $update_order_info)
    {
        try {
            if (Auth::user()->hasPermission('branch_all')) {
                $order = Order::where('in_order_id', $in_order_id)->update($update_order_info);
            } else {
                $order = Order::where(['in_order_id' => $in_order_id, 'in_branch_id' => Auth::user()->branch_id])->update($update_order_info);
            }
            if ($order) {
                return true;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function delete_pending_quotation_by_id($id)
    {
        try {
            $pending = PendingQuotation::where('stn_qtn_ord_no', $id)->update(['is_deleted' => 1]);
            if ($$pending) {
                return true;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function update_shipment_order($id)
    {
        try {
            if (Auth::user()->hasPermission('branch_all')) {
                $order = Order::where('in_uniq_order_id', $id)->update(['is_shipment_pending' => 1]);
            } else {
                $order = Order::where(['in_uniq_order_id' => $id, 'in_order_id' => Auth::user()->branch_id])->update(['is_shipment_pending' => 1]);
            }
            if ($order) {
                return true;
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
    public function update_quot_status($in_quot_id, $status_quotation)
    {
        try {
            if (Auth::user()->hasPermission('branch_all')) {
                $quote = QuatationAdd::where('in_quot_num', $in_quot_id)->update($status_quotation);
            } else {
                $quote = QuatationAdd::where(['in_quot_num' => $in_quot_id, 'in_branch_id' => Auth::user()->branch_id])->update($status_quotation);
            }
            if ($quote) {
                return true;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function getOrder(Request $request)
    {
        try {
            ini_set('memory_limit', '-1');
            $data = $request->all();
            $query = Order::select(
                'p.dt_created as dt_date_created',
                'p.stn_reason',
                'p.int_id',
                'p.stn_qtn_ord_no',
                'tbl_order.in_order_id',
                'tbl_order.in_uniq_order_id',
                'tbl_order.in_qoute_uniqu_id',
                'tbl_order.is_shipment_pending',
                'tbl_order.in_cust_id',
                'tbl_order.lead_from',
                'tbl_order.st_cust_order_num',
                'tbl_order.dt_cust_order_date',
                'tbl_order.st_ord_ship_adds',
                'tbl_order.st_ord_ship_state',
                'tbl_order.st_ord_ship_pincode',
                'tbl_order.stn_pdf_name',
                'tbl_order.st_ord_ship_city',
                'tbl_order.in_ord_ship_tel',
                'tbl_order.st_ord_ship_email',
                'tbl_order.flg_same_as_bill_add',
                'tbl_order.st_qoute_enq_no',
                'tbl_order.st_ord_tin_no',
                'tbl_order.st_ord_bank_id',
                'tbl_order.flt_ord_saletax_id',
                'tbl_order.flt_ord_net_total',
                'tbl_order.flt_ord_saletax_amt',
                'tbl_order.flt_ord_frig_pack',
                'tbl_order.flt_ord_total',
                'tbl_order.in_del_period',
                'tbl_order.int_ord_status',
                'tbl_order.dt_created',
                'tbl_order.flg_is_order_closed',
                'c.st_com_name',
                'q.in_quot_id',
                'q.is_order_pending',
                'c.st_cust_mobile',
                'c.st_cust_email',
                'qd.st_part_no',
                'qd.in_ord_pro_desc',
                'qd.in_ord_pro_maker',
                'qd.flt_ord_pro_price',
                'qd.flt_ord_pro_disct',
                'qd.in_ord_pro_qty',
                'tbl_order.in_branch_id'
            )

                ->leftJoin('tbl_order_details as qd', 'tbl_order.in_order_id', '=', 'qd.in_order_id')
                ->leftJoin('tbl_pending as p', 'p.stn_qtn_ord_no', '=', 'tbl_order.in_uniq_order_id')
                ->leftJoin('tbl_quotation as q', 'tbl_order.in_qoute_uniqu_id', '=', 'q.in_quot_num')
                ->join('tbl_customer as c', 'tbl_order.in_cust_id', '=', 'c.in_cust_id')
                ->where('tbl_order.flg_deleted', 0)
                ->groupBy('tbl_order.in_order_id')
                ->orderBy('tbl_order.in_order_id', 'desc');

            if (!Auth::user()->hasPermission('branch_all')) {
                $query->where('tbl_order.in_branch_id', Auth::user()->branch_id);
            }

            if (isset($data['branch_select']) && !empty($data['branch_select'])) {
                $query->where('tbl_order.in_branch_id', (int) $data['branch_select']);
            }

            if (isset($data['date_range']) && !empty($data['date_range'])) {
                $d = explode('|', $data['date_range']);
                $from_date = $d[0];
                $to_date = $d[1];
                $date = new \Carbon\Carbon($from_date);
                $start = $date->format('Y-m-d');
                $end_date = new \Carbon\Carbon($to_date);
                $end_date->addDay('+1');
                $end = $end_date->format('Y-m-d');
                $query->whereBetween('tbl_order.dt_created', [$start, $end]);

            }
            $branch_wise = Config::get('constant.branch_wise');
            if (isset($data['search']['value']) && !empty($data['search']['value'])) {
                $query->where('c.st_com_name', 'like', '%' . $data['search']['value'] . '%')
                    ->orWhere('tbl_order.in_uniq_order_id', 'like', '%' . $data['search']['value'] . '%')
                    ->orWhere('tbl_order.st_cust_order_num', 'like', '%' . $data['search']['value'] . '%')
                    ->orWhere('tbl_order.flt_ord_net_total', 'like', '%' . $data['search']['value'] . '%')
                    ->orWhere('tbl_order.lead_from', 'like', '%' . $data['search']['value'] . '%')
                    ->orWhere('qd.st_part_no', 'like', '%' . $data['search']['value'] . '%');
            }

            $orders = '';
            if (isset($data['search']['value']) && !empty($data['search']['value'])) {
                $d = $query->where('qd.st_part_no', $data['search']['value'])->get()->toArray();
                $orders = Datatables::of($d);
            } else {
                $orders = Datatables::eloquent($query);
            }
            if (Auth::user()->hasPermission('delete_order')) {
                $action_btn[] = '<div class="table-data-feature" style="justify-content:  center;"><button row-id="" class="item delete" data-toggle="tooltip" data-placement="top" title="Delete"><i class="zmdi zmdi-delete text-danger"></i></button></div>';
            }
            if (Auth::user()->hasPermission(['update_order', 'delete_order'])) {
                $orders->addColumn('actions', function ($orders) use ($action_btn) {
                    return '<div class="table-data-feature" style="justify-content:  center;">' . implode('', $action_btn) . '</div>';

                });
            } else {
                $orders->addColumn('actions', function ($orders) {
                    return '<div class="table-data-feature" style="justify-content:  left;"><button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="View Only"><i class="fa fa-eye text-primary"></i></button></div>';
                });
            }
            $orders->addColumn('reason', function ($orders) {
                if ($orders['is_shipment_pending'] == 0) {
                    if (empty($orders['stn_qtn_ord_no'])) {
                        if (Auth::user()->hasPermission(['add_partialorder', 'update_partialorder'])) {
                            if ($orders['flg_is_order_closed'] != 0) {
                                return '<div class="table-data-feature" style="justify-content:  left;"><div class="table-data-feature text-success">&nbsp &nbsp<button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Order Dispatched"><i class="fa fa-truck text-success"></i>></button></div></div>';
                            } else {
                                return '<div class="table-data-feature" style="justify-content:  left;"><div class="table-data-feature text-warning">&nbsp</div> <div class="table-data-feature"><button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Add More"><i class="fa add fa-plus text-warning"></i></button></div></div></div>';
                            }
                        } else {
                            return '<div class="table-data-feature" style="justify-content:  left;"><div class="table-data-feature text-warning">&nbsp</div> <div class="table-data-feature"><button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Add More"><i class="fa add fa-plus text-warning"></i></button></div></div></div>';
                        }
                    } else {
                        return '<div class="table-data-feature" style="justify-content:  left;"><div class="table-data-feature text-secondary view">&nbsp &nbsp<button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="View"><i class="fa fa-eye text-primary"></i></button></div></div>';
                    }
                } else {
                    if (!empty($orders['stn_reason'])) {
                        return '<div class="table-data-feature" style="justify-content:  left;"><div class="table-data-feature text-secondary view">&nbsp &nbsp<button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="View"><i class="fa fa-eye text-primary"></i></button></div></div>';
                    } else {
                        return '<div class="table-data-feature" style="justify-content:  left;"><div class="table-data-feature text-success">&nbsp &nbsp<button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Order Dispatched"><i class="fa fa-truck text-success"></i>></button></div></div>';
                    }
                }
            })->addColumn('operation', function ($orders) {
                $operation = '<div class="table-data-feature" style="display: flex;
                justify-content:  left;">';
                if ($orders['flg_is_order_closed'] == 0) {
                    if (Auth::user()->hasPermission(['add_partialorder', 'update_partialorder'])) {
                        $operation .= '<div class="table-data-feature text-primary" style="justify-content:  left;">&nbsp<button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Partial Order ?"><i class="fa generate_order fa-shopping-cart text-primary"></i></button></div><div class="table-data-feature  text-danger"> &nbsp <b></b> &nbsp &nbsp</div> <div class="table-data-feature"><button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Close ?"><i class="fa fa-close close_order text-danger"></i></button></div>';
                    } else {
                        $operation .= '<div class="table-data-feature text-danger" style="justify-content:  left;">&nbsp<button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Not allowed"><i class="fa fa-ban text-danger"></i>
                        </button></div> ';
                    }
                } elseif ($orders['flg_is_order_closed'] == 1) {
                    $operation .= '<div class="table-data-feature text-success" style="justify-content:  left;">&nbsp<button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Order Created / Closed"><i class="fa fa-truck text-success"></i></button></div><div class="table-data-feature"><div class="table-data-feature text-success">&nbsp <b> </b></div>';
                }

                $pdf_url = '';
                if (\File::exists(public_path('/pdf_' . date('Y') . '/' . $orders['stn_pdf_name']))) {
                    $pdf_url = \URL::to('/') . '/pdf_' . date('Y') . '/' . $orders['stn_pdf_name'];
                } elseif (\File::exists(public_path('/pdf_' . date("Y", strtotime("-1 year")) . '/' . $orders['stn_pdf_name']))) {
                    $pdf_url = \URL::to('/') . '/pdf_' . date("Y", strtotime("-1 year")) . '/' . $orders['stn_pdf_name'];
                } else {
                    $pdf_url = \URL::to('/') . '/quotationpdf/' . $orders['stn_pdf_name'];
                }
                $pdf = '&nbsp<div class="table-data-feature text-success"
                justify-content:  left;"><div class="table-data-feature text-success"
                justify-content:  left;">&nbsp  &nbsp<a href="' . $pdf_url . '" target="_blank"><div class="table-data-feature text-success"><button row-id="" class="item" data-toggle="tooltip" data-placement="top" title="Download"><i class="zmdi zmdi-download text-success"></i></button></div></a><div></div>';
                $operation .= $pdf;
                return $operation;

            })->addColumn('in_cust_id_val', function ($orders) {
                if (isset($orders['in_cust_id'])) {
                    return $orders['in_cust_id'];
                }
            })->rawColumns(['actions' => 'actions', 'reason' => 'reason', 'status' => 'status', 'in_cust_id_val' => 'in_cust_id_val', 'operation' => 'operation']);

            $orders->editColumn('dt_created', function ($orders) {
                $date = $orders['dt_created'];
                if (!empty($date)) {
                    return date('d-m-Y', strtotime($date));
                }
            })->editColumn('in_cust_id', function ($orders) {
                if (isset($orders['in_cust_id'])) {
                    return $orders['st_com_name'];
                }
            })->editColumn('in_quot_id', function ($orders) {
                if (!empty($orders['in_quot_id'])) {
                    return $orders['in_quot_id'];
                }
            })->editColumn('st_qoute_enq_no_text', function ($orders) {
                if (!empty($orders['stn_reason'])) {
                    return $orders['stn_reason'];
                }
            })->editColumn('is_shipment_pending', function ($orders) {
                if ($orders['is_shipment_pending'] == 0) {
                    return 'Shipment Pending';
                } elseif ($orders['is_shipment_pending'] == 1) {
                    return 'Shipment Done';
                } else {
                    return 'Shipment Pending';
                }
            })->editColumn('in_branch_id', function ($orders) use ($branch_wise) {
                if (!empty($orders['in_branch_id'])) {
                    if (isset($branch_wise[$orders['in_branch_id']])) {
                        return $branch_wise[$orders['in_branch_id']];
                    }
                }
            });


            return $orders->make(true);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function insert_quot_reason($insert_quot_reason)
    {
        try {
            $pending_update = PendingQuotation::insertGetId($insert_quot_reason);
            if (!empty($pending_update)) {
                return $pending_update;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
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
                    "st_com_name" => 'required',
                    'order_no' => 'required',
                    'order_date' => 'required',
                    "auto_pop_cust_name" => 'required',
                    "st_cust_mobile" => 'required',
                    "auto_pop_state" => 'required',
                    "preparing_by" => 'required',
                    "lead_from" => 'required',
                    'auto_pop_addr' => 'required',
                    'auto_pop_state' => 'required',
                    'auto_pop_city' => 'required',
                    'auto_pop_pincod' => 'required',
                    'auto_pop_phone' => 'required',
                    'auto_pop_email' => 'required',
                    'auto_pop_landline' => 'required',
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

    public function calculateTotal($quotation_details)
    {
        $total = 0;

        foreach ($quotation_details as $product) {
            $total += $product['in_pro_qty'] * $product['fl_pro_unitprice'];
        }

        return $total;
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
            # Close order
            $data = $request->all();
            $result = [];
            if (empty($data['order_id'])) {
                return back()->with([
                    'message' => 'Unable to get the order id.',
                ]);
            }
            $result['order_info'] = $this->get_oeder_by_id($data['order_id']);
            $in_cust_id = $result['order_info']['in_cust_id'];
            $result['order_details'] = $this->get_oeder_details($data['order_id']);
            $result['customer_info'] = $this->get_customer_by_id($in_cust_id);
            $result['format'] = $this->get_PDF_format_by_id($result['order_info']['bill_add_id']);
            $data['order_data'] = View::make("office.order.preview_close_order", $result)->render();
            return json_encode($data);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function closeOrderId(Request $request, $order_id)
    {
        try {
            # Close order by id
            $data = [];
            $data['order_id'] = $order_id;
            $chek_partially_order_status = $this->chek_partially_order_status($order_id);
            if ($chek_partially_order_status) {
                return redirect()->route('show_partial_order')->with(['message' => 'Order has been fully completed.']);
            }
            $data['order_info'] = $this->get_oeder_by_id($order_id);
            if (!is_array($data['order_info'])) {
                return redirect()->route('show_partial_order')->with(['message' => 'Please generate a order first.']);
            }

            $in_branch_id = \Auth::user()->branch_id;
            $admin_user_id = \Auth::user()->id;
            $in_cust_id = $data['order_info']['in_cust_id'];
            $data['product_list'] = $this->get_product_list();
            $data['oeder_details'] = $this->get_oeder_details($order_id);
            $data['customer_info'] = $this->get_customer_by_id($in_cust_id);
            if (Auth::user()->hasPermission('branch_all')) {
                $courier = Courier::where('is_deleted', 0)->get();
            } else {
                $courier = Courier::where(['is_deleted' => 0, 'in_branch_id' => Auth::user()->branch_id])->get();
            }
            if (!empty($courier)) {
                $courier = $courier->pluck('st_courier_name', 'in_courier_id')->toArray();
            } else {
                $courier = [];
            }
            $data['courier'] = $courier;
            $data['preparing_by'] = 'prepare by';
            $branch = Config::get('constant.branch_wise');
            $branchname = $branch[$in_branch_id];
            $in_uniq_order_id = $this->generate_partially_order_no($branchname, $in_branch_id, "partial-order");
            $insert_order_arr = [
                'in_uniq_order_id' => $in_uniq_order_id,
                'in_qoute_uniqu_id' => $data['order_info']['in_qoute_uniqu_id'],
                'in_partlyorder_id' => $order_id,
                'in_cust_id' => $in_cust_id,
                'st_cust_order_num' => $data['order_info']['st_cust_order_num'],
                'dt_cust_order_date' => $data['order_info']['dt_cust_order_date'],
                'st_partlyord_ship_adds' => $data['order_info']['st_ord_ship_adds'],
                'st_partlyord_ship_state' => $data['order_info']['st_ord_ship_state'],
                'st_partlyord_ship_city' => $data['order_info']['st_ord_ship_city'],
                'st_partlyord_ship_pincode' => $data['order_info']['st_ord_ship_pincode'],
                'in_partlyord_ship_tel' => $data['order_info']['in_ord_ship_tel'],
                'st_landline' => $data['order_info']['st_landline'],
                'st_partlyord_ship_email' => $data['order_info']['st_ord_ship_email'],
                'flg_same_as_bill_add' => $data['order_info']['flg_same_as_bill_add'],
                'st_ord_tin_no' => $data['order_info']['st_ord_tin_no'],
                'st_pay_turm' => $data['order_info']['st_pay_turm'],
                'st_ext_note' => trim($data['order_info']['st_ext_note']),
                'flt_ord_saletax_id' => $data['order_info']['flt_ord_saletax_id'],
                'flt_ord_net_total' => $data['order_info']['flt_ord_net_total'],
                'flt_ord_saletax_amt' => $data['order_info']['flt_ord_saletax_amt'],
                'flt_ord_frig_pack' => $data['order_info']['flt_ord_frig_pack'],
                'flt_ord_total' => $data['order_info']['flt_ord_total'],
                'lead_from' => $data['order_info']['lead_from'],
                'int_ord_status' => 2,
                'log_in_id' => $admin_user_id,
                'in_branch_id' => $in_branch_id,
                'is_payment_paid' => 0,
                'st_courier_option' => $data['order_info']['st_courier_option'],
                'dt_created' => date('Y-m-d h:i:s'),
                'st_cont_person_for_payment' => $data['order_info']['st_cont_person_for_payment'],
                'int_cont_num_for_payment' => $data['order_info']['int_cont_num_for_payment']
            ];

            $inserted_order_id = $this->isert_orders($insert_order_arr);
            if ($inserted_order_id > 0 && $inserted_order_id != '' && $inserted_order_id != false) {
                foreach ($data['oeder_details'] as $partial_order_details_k => $partial_order_details_v) {
                    $insert_order_detail_arr = [
                        'in_partparaint_ord_id' => $inserted_order_id,
                        'in_partlyorder_id' => (int) $order_id,
                        'st_part_no' => $partial_order_details_v['st_part_no'],
                        'in_partlyord_prod_id' => 0,
                        'in_partlyord_pro_desc' => $partial_order_details_v['in_ord_pro_desc'],
                        'in_partlyord_delivery_period' => $partial_order_details_v['in_ord_delivery_period'],
                        'in_partlyord_pro_maker' => $partial_order_details_v['in_ord_pro_maker'],
                        'in_partlyord_pro_qty' => $partial_order_details_v['in_ord_pro_qty'],
                        'in_balance_pro_qty' => 0,
                        'in_sent_pro_qty' => $partial_order_details_v['in_ord_pro_qty'],
                        'flt_partlyord_pro_price' => $partial_order_details_v['flt_ord_pro_price'],
                        'flt_partlyord_pro_disct' => $partial_order_details_v['flt_ord_pro_disct'],
                        'flt_partlyord_pro_net_price' => $partial_order_details_v['flt_ord_pro_net_price'],
                        'flt_partlyord_pro_row_total' => $partial_order_details_v['flt_ord_pro_row_total'],
                        'in_partlyord_pro_status' => 0,
                        'dt_created' => date('Y-m-d h:i:s')
                    ];
                    $inserted_orders_details = $this->isert_orders_details($insert_order_detail_arr, $partial_order_details_v['in_ord_detail_id']);
                }
                $update_order_info = array(
                    'flg_is_order_closed' => 1,
                    'flg_deleted' => 0,
                );
                $update_orderDetail_status = array(
                    'flg_partord_status' => 1,
                    'flg_is_partial_checked' => 0
                );

                $this->update_part_order_detail_status($order_id, $update_orderDetail_status);
                $this->update_order($order_id, $update_order_info);
                $this->delete_pending_quotation($data['order_info']['in_uniq_order_id']);
                $this->update_shipment_order($data['order_info']['in_uniq_order_id']);
                $pdfFilePath = "order_" . time() . "_" . date('dmy') . ".pdf";
                $data['order_details'] = $this->get_order_details_data($inserted_order_id, $in_cust_id);
                $data['order_info'] = $this->get_partial_order_info($inserted_order_id, $in_cust_id);
                $data['customer_info'] = $this->get_customer_by_id($in_cust_id);
                // $data['currency']   = $currencyCodes[$cur[$c_format]];
                $data['file_path'] = $pdfFilePath;
                $data['email'] = \Auth::user()->email;
                $data['cc_email'] = \Auth::user()->cc_email;
                $data['format'] = 'address'; // $this->get_PDF_format_by_id('address');
                dispatch(new CloseOrder($data));
                return redirect()->route('show_partial_order')->with(['message' => 'Partially order generated successfully.']);
            } else {
                return back()->with([
                    'message' => 'Something went wrong while generating partial order. Please try again'
                ]);
            }
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }

    public function addOrderReason(Request $request)
    {
        try {
            # Add reason
            $data = $request->all();
            $reason_mode = 0;
            $stn_reason = '';
            $is_delete = 0;
            if ($data['reason_mode'] == 1) {
                $reason_mode = 1;
            }

            if (isset($data['status_quotation']) != '') {
                $status_quotation = ['is_order_pending' => $data['status_quotation']];
                if ($data['status_quot_text'] != 'Select Status') {
                    $this->update_quot_status($data['quotation_id'], $status_quotation);
                    $stn_reason = $data['status_quot_text'];
                    $is_delete = 1;
                }
            } else {
                $stn_reason = $data['reason_name'];
            }
            $insert_quot_reason = [
                'int_qd_no' => $data['quote_id'],
                'stn_qtn_ord_no' => $data['order_number'],
                'stn_amt' => $data['order_value'],
                'dt_date' => date('Y-m-d h:i:s', strtotime($data['order_date'])),
                'int_cust_id' => $data['customer_id'],
                'stn_reason' => $stn_reason,
                'int_reason_mode' => $reason_mode,
                'int_branch_id' => \Auth::user()->branch_id,
                'user_id' => \Auth::user()->id,
                'dt_created' => date('Y-m-d h:i:s'),
                'dt_modify' => date('Y-m-d h:i:s'),
                'is_deleted' => $is_delete
            ];
            if ($this->insert_quot_reason($insert_quot_reason)) {
                $response_array = 1;
            } else {
                $response_array = 0;
            }
            return response()->json(['code' => 200, 'success' => 'Reason added successfully.']);
        } catch (Exception $ex) {
            Log::debug($ex);
        }
    }
}