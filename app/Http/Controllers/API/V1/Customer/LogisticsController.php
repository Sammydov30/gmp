<?php

namespace App\Http\Controllers\API\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateInterStateShipmentRequest;
use App\Models\Customer;
use App\Models\FundingHistory;
use App\Models\Logistic;
use App\Models\LogisticInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LogisticsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $accounts=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        // return response()->json($accounts, 200);
    }

    public function store(CreateInterStateShipmentRequest $request)
    {
        $user=auth()->user();
        if ($request->gmppayment=='1') {
            $this->checkWallet($request->totalamount);
            $p_status='1';
        }else{
            $p_status='0';
        }
        $logisticid="GMPLOG".time();
        $logistics = Logistic::create([
            "logisticid"=>$logisticid,
            "pickupvehicle"=>$request->pickupvehicle,
            "gmpid"=>$request->gmpid,
            "pickupdate"=>$request->pickupdate,
            "gmppayment"=>$request->gmppayment,
            "p_status"=>$p_status,
            "deliverymode"=>$request->deliverymode,
            "pickupcenter"=>$request->pickupcenter,
            "cname"=>$request->cname,
            "cphone"=>$request->cphone,
            "caddress"=>$request->caddress,
            "rname"=>$request->rname,
            "rphone"=>$request->rphone,
            "raddress"=>$request->raddress,
            "fromregion"=>$request->sourceregion,
            "toregion"=>$request->destinationregion,
            "totalweight"=>$request->totalweight,
            "amount"=>$request->totalamount,
        ]);
        for ($i=0; $i < count($request->stype[$i]); $i++) {
            LogisticInfo::create([
                "shipment_id"=>$logistics->id,
                "type"=>$request->stype[$i],
                "item"=>$request->sitem[$i],
                "name"=>$request->sname[$i],
                "weight"=>$request->sweight[$i],
                "weighttype"=>'1',
                "quantity"=>'1',
                "length"=>'1',
                "width"=>'1',
                "height"=>'1',
                "value_declaration"=>$request->svalue_declaration[$i]
            ]);
        }
        if ($request->gmppayment=='1') {
            $this->chargeWallet($request->totalamount);
            $createrequest = Http::withHeaders([
                "content-type" => "application/json",
                // "Authorization" => "Bearer ",
            ])->post(env('SOLVENT_BASE_URL').'/api/shipment/createshipment', [
                "pickupvehicle"=>$request->pickupvehicle,
                "gmpid"=>$request->gmpid,
                "pickupdate"=>$request->pickupdate,
                "gmppayment"=>$request->gmppayment,
                "p_status"=>$p_status,
                "deliverymode"=>$request->deliverymode,
                "pickupcenter"=>$request->pickupcenter,
                "cname"=>$request->cname,
                "cphone"=>$request->cphone,
                "caddress"=>$request->caddress,
                "rname"=>$request->rname,
                "rphone"=>$request->rphone,
                "raddress"=>$request->raddress,
                "sourceregion"=>$request->sourceregion,
                "destinationregion"=>$request->destinationregion,
                "totalweight"=>$request->totalweight,
                "totalamount"=>$request->totalamount,
                "stype"=>$request->stype,
                "item"=>$request->sitem,
                "sname"=>$request->sname,
                "sweighttype"=>$request->sweighttype,
                "sweight"=>$request->sweight,
                "squantity"=>$request->squantity,
                "slength"=>$request->slength,
                "swidth"=>$request->swidth,
                "sheight"=>$request->sheight,
                "svalue_declaration"=>$request->svalue_declaration
            ]);
            FundingHistory::create([
                'fundingid' => $logistics->id,
                'gmpid' => $logistics->gmpid,
                'amount'=>$request->totalamount,
                'ftime'=>time(),
                'currency'=>'NGN',
                'status'=>'1',
                'type'=>'2',
                'which'=>'2'
            ]);
            $res=$createrequest->json();
            if (!$res['status']) {
                return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
            }else{
                if ($res['status']=="error") {
                    return response()->json(["message" => $res['message'], "status" => "error"], 400);
                }else{
                    Logistic::where("id", $logistics->id)->update([
                        "trackingid"=>$res['data']['trackingid'],
                        "orderid"=>$res['data']['orderid'],
                    ]);
                    return response()->json($res, 201);
                }
            }

        }else{
            ///Pay with payment gateway
            $useragent=$_SERVER['HTTP_USER_AGENT'];
            // $pamount=(int)$amount*100;
            $pamount=(int)$request->totalamount;

            $paymentrequest = Http::withHeaders([
                "Authorization" => "Bearer ".env('FW_KEY'),
                "content-type" => "application/json",
                "Cache-Control" => "no-cache",
                "User-Agent" => $useragent,
            ])->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $logisticid,
                'amount' => $pamount,
                'currency' => 'NGN',
                'redirect_url' => 'https://gavice.ng/gmp-payment',
                "customer" => [
                    'email' => $user->email,
                ],
                "customizations" => [
                    "title" => "Gavice Market Place",
                    "logo" => "https://gavice.ng/img/logo/logo.png"
                ]
            ]);
            $payy=$paymentrequest->json();
            $response=[
                "message" => "Request Checked Out",
                "paymentrequest"=>$payy,
                'logistics' => $logistics,
                "status" => "success"
            ];
            return response()->json($response, 201);

        }

    }

    public function getquote(CreateInterStateShipmentRequest $request)
    {
        if ($request->stype=='2') {
            if (empty($request->sitem)) {
                return response()->json(["message" => "A Special Item was not selected", "status" => "error"], 400);
            }
        }
        $createrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL').'/api/shipment/getquote', [
            "pickupvehicle"=>$request->pickupvehicle,
            "deliverymode"=>$request->deliverymode,
            "pickupcenter"=>$request->pickupcenter,
            "sourceregion"=>$request->sourceregion,
            "destinationregion"=>$request->destinationregion,
            "itemtype"=>serialize($request->stype),
            "sitem"=>serialize($request->sitem),
            "itemweight"=>serialize($request->sweight),
            "itemvalue"=>serialize($request->svalue_declaration)
        ]);
        $res=$createrequest->json();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "status" => "error"], 400);
            }else{
                return response()->json($res, 201);
            }
        }
    }

    public function checkWallet($amount) {
        $user=auth()->user();
        $check=Customer::where('gmpid', $user->gmpid)->first();
        if ($check) {
            $balance=$check->ngnbalance;
            if ($balance<$amount) {
                return response()->json(["message" => "Insuficient Funds", "status" => "error"], 400);
            }
        }else{
            return response()->json(["message" => "An Error Occured", "status" => "error"], 400);
        }
    }

    public function chargeWallet($amount) {
        $user=auth()->user();
        $check=Customer::where('gmpid', $user->gmpid)->first();
        $balance=$check->ngnbalance;
        $newbal=$balance-$amount;
        Customer::where('gmpid', $user->gmpid)->update(['ngnbalance'=>$newbal]);
    }

    public function verifypayment(Request $request)
    {
        $tx_ref=$request->tx_ref;
        if (empty($tx_ref)) {
            return response()->json(["message"=>"Verification error. No Transaction Id given.", "status"=>"error"], 400);
        } else {
            $tx=Logistic::where('logisticid', $tx_ref)->first();
            if (!$tx) {
                return response()->json(["message"=>"Shipment doesn't exist", "status"=>"error"], 400);
            }
            if ($tx->status=="1") {
                return response()->json(["message"=>"Transaction value already given", "status"=>"error"], 400);
            }
            $amount = $tx->amount;
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
                Logistic::where('logisticid', $tx_ref)->update([
                    'p_status' => '2'
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

                    Logistic::where('logisticid', $tx_ref)->update([
                        'p_status' => '1'
                    ]);
                    $logistics=Logistic::where('logisticid', $tx_ref)->first();
                    $logisticsinfo=LogisticInfo::where('shipment_id', $logistics->id)->get();
                    $createrequest = Http::withHeaders([
                        "content-type" => "application/json",
                        // "Authorization" => "Bearer ",
                    ])->post(env('SOLVENT_BASE_URL').'/api/shipment/createshipment', [
                        "pickupvehicle"=>$logistics->pickupvehicle,
                        "gmpid"=>$logistics->gmpid,
                        "pickupdate"=>$logistics->pickupdate,
                        "gmppayment"=>$logistics->gmppayment,
                        "p_status"=>$logistics->p_status,
                        "deliverymode"=>$logistics->deliverymode,
                        "pickupcenter"=>$logistics->pickupcenter,
                        "cname"=>$logistics->cname,
                        "cphone"=>$logistics->cphone,
                        "caddress"=>$logistics->caddress,
                        "rname"=>$logistics->rname,
                        "rphone"=>$logistics->rphone,
                        "raddress"=>$logistics->raddress,
                        "sourceregion"=>$logistics->fromregion,
                        "destinationregion"=>$logistics->toregion,
                        "totalweight"=>$logistics->totalweight,
                        "totalamount"=>$logistics->amount,
                        //Package Informations
                        "stype"=>$logisticsinfo->type,
                        "item"=>$logisticsinfo->item,
                        "sname"=>$logisticsinfo->name,
                        "sweighttype"=>$logisticsinfo->weighttype,
                        "sweight"=>$logisticsinfo->weight,
                        "squantity"=>$logisticsinfo->quantity,
                        "slength"=>$logisticsinfo->length,
                        "swidth"=>$logisticsinfo->width,
                        "sheight"=>$logisticsinfo->height,
                        "svalue_declaration"=>$logisticsinfo->value_declaration
                    ]);
                    $res=$createrequest->json();
                    if (!$res['status']) {
                        return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
                    }else{
                        if ($res['status']=="error") {
                            return response()->json(["message" => $res['message'], "status" => "error"], 400);
                        }else{
                            Logistic::where("id", $logistics->id)->update([
                                "trackingid"=>$res['data']['trackingid'],
                                "orderid"=>$res['data']['orderid'],
                            ]);
                            return response()->json($res, 201);
                        }
                    }

                }else{
                    Logistic::where('logisticid', $tx_ref)->update([
                        'p_status' => '2'
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
