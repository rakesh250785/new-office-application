<?php

namespace App\Http\Controllers\ClientUser\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Owner;
use Carbon\Carbon;
use DataTables;

use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function __construct() {
        //
    }

    public function getCustomerList(){
       //
    }

    public function addCustomer(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
            ]);
            if ($validator->fails()) {
                //
             }
        } catch (\Throwable $th) {
            Log::error('Something went wrong', [
                'message' => $th->getMessage(),
                'exception' => $th,
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}

