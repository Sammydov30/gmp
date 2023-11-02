<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalRequest;
use App\Models\Account;
use App\Models\Doctor;
use App\Models\WithdrawalHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WithdrawalController extends Controller
{

    public function index()
    {
        $result = DB::table('withdrawal_histories');

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
        $account=Account::where('id', $request->account)->first();
        if (!$account) {
            return response()->json(["message" => "Account Doesn't Exist", "status" => "error"], 400);
        }
        //check balance
        $check=Doctor::where('id', $user->id)->first();
        if ($check) {
            $balance=$check->balance;
            if ($balance<$request->amount) {
                return response()->json(["message" => "Insuficient Funds", "status" => "error"], 400);
            }
        }else{
            return response()->json(["message" => "An Error Occured", "status" => "error"], 400);
        }
        $txref='WT'.time();
        $withdrawalrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('PAYSTACK_KEY_TEST'),
        ])->post('https://api.paystack.co/transfer', [
            "source" => "balance",
            "reason" => "Cash Out",
            "amount" => (float)$request->amount,
            "recipient" => $account->recipient,
            "reference" => $txref
        ]);
        $res=$withdrawalrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while requesting for Withdrawal", "status" => "error"], 400);
        }else{
            $details=$res['data'];
            $transfercode=$res['data']['transfer_code'];
            //debit
            $newbal=$balance-$request->amount;
            Doctor::where('id', $user->id)->update(['balance'=>$newbal]);
            $account = WithdrawalHistory::create([
                'doctorid' => $user->doctorid,
                'amount' => $request->amount,
                'accountid' => $request->account,
                'tx_ref'=> $txref,
                'wtime'=> time(),
                'status'=> '1',
                'transfer_code' => $transfercode,
            ]);
            $response=[
                "message" => "Withdrawal Requested Successfully",
                'transaction' => $details,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }
    }

}
