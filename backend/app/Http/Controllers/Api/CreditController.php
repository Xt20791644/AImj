<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function transactions(Request $request) {
        return $request->user()->creditTransactions()->paginate(20);
    }
    public function balance(Request $request) {
        return response()->json(['balance' => $request->user()->credits]);
    }
}
