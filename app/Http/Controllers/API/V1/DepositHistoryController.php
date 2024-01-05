<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Models\Customer;
use App\Models\DepositHistory;
use App\Models\FundingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DepositHistoryController extends Controller
{

    public function index()
    {
        $result = DepositHistory::with('customer');

        if (request()->input("gmpid") != null) {
            $search=request()->input("gmpid");
            $result->where('gmpid', $search);
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
        $transaction=DepositHistory::find($id);
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

    public function fundAccount(DepositRequest $request)
    {
        $error = array();
        $customer=auth()->user();
        $email = $customer->email;
        if(empty($email)){
            array_push($error, 'Email not Set');
        }
        $amount = $request->amount;
        $depositid='GMP_D'.time();
        $req_date=time();

        $error = array();
        if (empty($error)) {
            $deposit = DepositHistory::create([
                'depositid' => $depositid,
                'gmpid' => $customer->gmpid,
                'amount'=>$amount,
                'wtime'=>$req_date,
                'currency'=>'NGN'
            ]);
            $useragent=$_SERVER['HTTP_USER_AGENT'];
            // $pamount=(int)$amount*100;
            $pamount=(int)$amount;

            $paymentrequest = Http::withHeaders([
                "Authorization" => "Bearer ".env('FW_KEY'),
                "content-type" => "application/json",
                "Cache-Control" => "no-cache",
                "User-Agent" => $useragent,
            ])->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $depositid,
                'amount' => $pamount,
                'currency' => 'NGN',
                'redirect_url' => "https://gavice.com/gmp-funding",
                "customer" => [
                    'email' => $email,
                ],
                "customizations" => [
                    "title" => "Gavice Market Place",
                    "logo" => "https://gavice.ng/img/logo/logo.png"
                ]
            ]);
            $payy=$paymentrequest->json();

            $response=[
                "message" => "Deposit Initiated",
                "deposit" => $deposit,
                "paymentrequest"=>$payy,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }else{
            $response=[
                "message" => $error,
                "status" => "error"
            ];
            //print_r($error); exit();
            return response()->json($response, 400);
        }
    }

    public function verifypayment(Request $request)
    {
        $tx_ref=$request->tx_ref;
        if (empty($tx_ref)) {
            return response()->json(["message"=>"Verification error. No Transaction Id given.", "status"=>"error"], 400);
        } else {
            $tx=DepositHistory::where('depositid', $tx_ref)->first();
            if (!$tx) {
                return response()->json(["message"=>"Deposit doesn't exist", "status"=>"error"], 400);
            }
            if ($tx->status=="1") {
                return response()->json(["message"=>"Transaction value already given", "status"=>"error"], 400);
            }
            $amount = $tx->amount;
            $userid=$tx->gmpid;
            $currency = 'NGN';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=$tx_ref",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                  "Authorization: Bearer ".env('FW_KEY'),
                  "Cache-Control: no-cache",
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $deposit=DepositHistory::where('depositid', $tx_ref)->update([
                    'status' => '2'
                ]);
                return response()->json([
                    'message' => "cURL Error #:" . $err,
                    'status' => "error"
                ], 400);
            } else {
                $transaction = json_decode($response, FALSE);
                //print_r($transaction); exit();
                if( ($transaction->status=="success") && ($transaction->data->status=="successful")
                && ($transaction->data->amount>=$amount) && ($transaction->data->currency=="NGN") ){
                    $ngnbalance=Customer::where('gmpid', $userid)->first()->ngnbalance;
                    Customer::where('gmpid', $userid)->update([
                        'ngnbalance' => $ngnbalance+$transaction->data->amount
                    ]);
                    FundingHistory::create([
                        'fundingid' => $tx->id,
                        'gmpid' => $tx->gmpid,
                        'amount'=>$transaction->data->amount,
                        'ftime'=>$tx->wtime,
                        'currency'=>$currency,
                        'status'=>'1',
                        'type'=>'1',
                        'which'=> '1'
                    ]);
                    DepositHistory::where('depositid', $tx_ref)->update([
                        'status' => '1'
                    ]);
                    $deposit=DepositHistory::where('depositid', $tx_ref)->first();
                    return response()->json([
                        'message' => 'Wallet Funded Successfully',
                        'details' => $deposit,
                        'status' => 'success'
                    ], 200);
                }else{
                    $deposit=DepositHistory::where('depositid', $tx_ref)->update([
                        'status' => '2'
                    ]);
                    return response()->json([
                        'message' => "Payment returned error: " . $transaction->message,
                        'status' => "error"
                    ], 400);
                }
            }

        }
    }

}
