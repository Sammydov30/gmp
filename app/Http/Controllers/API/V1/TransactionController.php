<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Customer\TransactionResource;
use App\Models\Account;
use App\Models\CustomerTransaction;
use App\Models\FundingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransactionController extends Controller
{

    public function index()
    {
        $result = FundingHistory::with('customer');


        if (request()->input("enddate") != null) {
            if (request()->input("startdate") == null) {
                return response()->json(["message" => "start-date required if end-date given.", "status" => "error"], 400);
            }
            $startdate= date('Y-m-d',strtotime(request()->input("startdate")));
            $enddate=date('Y-m-d',strtotime(request()->input("enddate")));
            $result->where(function($query) use($startdate, $enddate){
                $query->whereBetween('created_at', array($startdate,$enddate));
            });
        }elseif(request()->input("startdate") != null){
            $startdate= date('Y-m-d',strtotime(request()->input("startdate")));
            $enddate=date("Y-m-d");
            $result->where(function($query) use($startdate, $enddate){
                $query->whereBetween('created_at', array($startdate,$enddate));
            });
        }
        if (request()->input("gmpid") != null) {
            $search=request()->input("gmpid");
            $result->where('gmpid', $search);
        }
        if ((request()->input("sortby")!=null) && in_array(request()->input("sortby"), ['id', 'created_at'])) {
            $sortBy=request()->input("sortby");
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
        //return response()->json($transactions, 200);
        return TransactionResource::collection($transactions);
    }

    public function show($id)
    {
        $transaction=FundingHistory::with('customer')->find($id);
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
