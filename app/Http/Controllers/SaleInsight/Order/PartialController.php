<?php

namespace App\Http\Controllers\SaleInsight\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PartialController extends Controller
{
    //
    public function generate_partially_order_no($branchname, $in_branch_id, $generate_No_for = "")
	{
        try {
            $initial3latters = substr($branchname, 0, 3);
            if(Auth::user()->hasPermission('branch_all')){
                $partialOrder = PartialOrder::whereDate('dt_created', '>=', Carbon::now()->format('Y-m-d'))->get();
            }else{
                $partialOrder = PartialOrder::where('in_branch_id', $in_branch_id)->whereDate('dt_created', '>=', Carbon::now()->format('Y-m-d'))->get();
            }
            $flg_type = '';
            if($generate_No_for != ""){
                $flg_type = "Part-";
            }
            if(!empty($partialOrder)){
                $number = count($partialOrder)+1;
                $unique_quote_no = $initial3latters."/".$flg_type.$number;
            }else{
                $unique_quote_no = $initial3latters."/".$flg_type."1";
            }
            return $unique_quote_no;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
	}

    public function isert_orders($insert_array){
        try {
            $insert = PartialOrder::insertGetId($insert_array); 
            if(!empty($insert)){
                return $insert;
            }
            return false;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
	}	

    public function get_order_details_data($order_id, $in_cust_id){
        try {
            if(Auth::user()->hasPermission('branch_all')){
                $partial_order_details = PartialOrderDetails::where('in_partparaint_ord_id', $order_id)->where('flg_deleted', 0)->get();
            }else{
                $partial_order_details = PartialOrderDetails::where(['in_partparaint_ord_id'=>$order_id,'branch_id'=>Auth::user()->branch_id ])->where('flg_deleted', 0)->get();
            }
            return $partial_order_details;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
	}

    public function get_partial_order_info($order_id, $in_cust_id){   
        try {
            $result = [];
            if(Auth::user()->hasPermission('branch_all')){
                $query = PartialOrder::where('in_partparaint_ord_id', $order_id)->where('in_cust_id', $in_cust_id)->where('flg_deleted', 0)->first();
            }else{
                $query = PartialOrder::where(['in_partparaint_ord_id'=>$order_id, 'in_branch_id'=>Auth::user()->branch_id])->where('in_cust_id', $in_cust_id)->where('flg_deleted', 0)->first();
            }
            if(!empty($query)){
                $result = $query->toArray();
            }
            return $result;
        } catch (Exception $ex) {
            Log::debug($ex);
        }
	}

}
