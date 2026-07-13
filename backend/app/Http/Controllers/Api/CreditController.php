<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function balance(Request $request)
    {
        return response()->json([
            'balance' => $request->user()->credits,
        ]);
    }

    public function transactions(Request $request)
    {
        $transactions = $request->user()
            ->creditTransactions()
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }
}
