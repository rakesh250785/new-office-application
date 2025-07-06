<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpencesController extends Controller
{
    public function testApi(){
        return response()->json(['name'=>'manoj', 'status'=> 'success']);
    }
}
