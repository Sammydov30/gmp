<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalRequest;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\FundingHistory;
use App\Models\WithdrawalHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WithdrawalController extends Controller
{

    public function index()
    {
        $result = WithdrawalHistory::with('customer');

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
        return response()->json($transactions, 200);
    }

    public function show($id)
    {
        $transaction=WithdrawalHistory::find($id);
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

    public function makewithdrawal(WithdrawalRequest $request)
    {
        $user=auth()->user();
        //check balance
        $check=Customer::where('gmpid', $user->gmpid)->first();
        if ($check) {
            if ($check->pin!=$request->pin) {
                return response()->json(["message" => "Incorrect PIN", "status" => "error"], 400);
            }
            $balance=$check->ngnbalance;
            if ($balance<$request->amount) {
                return response()->json(["message" => "Insuficient Funds", "status" => "error"], 400);
            }
        }else{
            return response()->json(["message" => "An Error Occured", "status" => "error"], 400);
        }

        $accountnumber= $request->accountnumber;
        $bankcode= $request->bank;
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/accounts/resolve', [
            "account_number"=> $accountnumber,
            "account_bank"=> $bankcode,
        ]);
        $res=$acctrequest->json();
        //print_r($res);
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }else{
            $details=$res['data'];
            $accountname=$details['account_name'];
        }

        $txref='GMPWT'.time();
        $date=date("d-m-Y");
        //debit
        $newbal=$balance-$request->amount;
        Customer::where('gmpid', $user->gmpid)->update(['ngnbalance'=>$newbal]);
        $withdrawal = WithdrawalHistory::create([
            'gmpid' => $user->gmpid,
            'amount' => $request->amount,
            'bank' => $request->bank,
            'accountnumber'=>$request->accountnumber,
            'accountname'=>$accountname,
            'withdrawalid'=> $txref,
            'wtime'=> time(),
            'narration'=>'GMP Withdrawal On '.$date,
            'currency'=>'NGN',
            'status'=> '0',
        ]);
        $response=[
            "message" => "Withdrawal Requested Successfully",
            'transaction' => $withdrawal,
            "status" => "success"
        ];
        return response()->json($response, 201);

    }

    public function confirmwithdrawal(Request $request)
    {
        if (empty($request->withdrawalid)) {
            return response()->json(["message"=>"Withdrawal Id is required", "status"=>"error"], 400);
        }
        $withdrawal=WithdrawalHistory::where('withdrawalid', $request->withdrawalid)->first();
        if (!$withdrawal) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        if ($withdrawal->status!='0') {
            return response()->json(["message"=>"Withdrawal already processed", "status"=>"error"], 400);
        }
        $date=date("d-m-Y");
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/transfers', [
            "account_number"=> $withdrawal->accountnumber,
            "account_bank"=> $withdrawal->bank,
            "amount"=> intval($withdrawal->amount),
            "narration"=> $withdrawal->narration,
            "currency"=> $withdrawal->currency,
            "reference"=> $withdrawal->withdrawalid,
            "debit_currency"=> $withdrawal->currency,
        ]);
        $res=$paymentrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }
        if ($res['status']=='error') {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }
        FundingHistory::create([
            'fundingid' => $withdrawal->id,
            'gmpid' => $withdrawal->gmpid,
            'amount'=>$withdrawal->amount,
            'ftime'=>time(),
            'currency'=>$withdrawal->currency,
            'status'=>'1',
            'type'=>'2',
            'which'=> '1'
        ]);
        $withdrawal=WithdrawalHistory::where('withdrawalid', $withdrawal->withdrawalid)->update([
            'status' => '1'
        ]);
        return response()->json([
            "message"=>"Withdrawal Successful",
            "status" => "success",
            'payment' => $withdrawal,
        ], 200);
    }

    public function declinewithdrawal(Request $request)
    {
        if (empty($request->withdrawalid)) {
            return response()->json(["message"=>"Withdrawal Id is required", "status"=>"error"], 400);
        }
        $withdrawal=WithdrawalHistory::where('withdrawalid', $request->withdrawalid)->first();
        if (!$withdrawal) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        if ($withdrawal->status!='0') {
            return response()->json(["message"=>"Withdrawal already processed", "status"=>"error"], 400);
        }
        $withdrawal=WithdrawalHistory::where('withdrawalid', $withdrawal->withdrawalid)->update([
            'status' => '2'
        ]);
        return response()->json([
            "message"=>"Withdrawal Declined",
            "status" => "success",
            'payment' => $withdrawal,
        ], 200);
    }

}
