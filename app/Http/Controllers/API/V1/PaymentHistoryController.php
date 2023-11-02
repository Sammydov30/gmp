<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CustomerTransaction;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PaymentHistoryController extends Controller
{

    public function index()
    {
        $result = PaymentHistory::with('doctor');

        if (request()->input("doctorid") != null) {
            $search=request()->input("doctorid");
            $result->where('doctorid', $search);
        }
        if ((request()->input("sortBy")!=null) && in_array(request()->input("sortBy"), ['id', 'created_at'])) {
            $sortBy=request()->input("sortBy");
        }else{
            $sortBy='id';
        }
        if ((request()->input("sortorder")!=null) && in_array(request()->input("sortorder"), ['asc', 'desc'])) {
            $sortOrder=request()->input("sortorder");
        }else{
            $sortOrder='desc';
        }
        if (!empty(request()->input("perpage"))) {
            $perPage=request()->input("perpage");
        } else {
            $perPage=10;
        }

        $transactions=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($transactions, 200);
    }

    public function show($id)
    {
        $transaction=PaymentHistory::find($id);
        if (!$transaction) {
            return response()->json(["message" => "Transaction Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Transaction found",
            'transaction' => $transaction,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

}
