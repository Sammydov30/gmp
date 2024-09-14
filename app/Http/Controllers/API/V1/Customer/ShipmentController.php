<?php

namespace App\Http\Controllers\API\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateInterStateShipmentRequest;
use App\Http\Requests\Customer\GetInterStateQuoteRequest;
use App\Http\Resources\API\V1\Customer\TrackShipmentResource;
use App\Jobs\Customer\SendTrackingNoJob;
use App\Models\Customer;
use App\Models\FundingHistory;
use App\Models\Logistic;
use App\Models\LogisticInfo;
use App\Models\PickupCenter;
use App\Models\Shipment;
use App\Models\ShipmentInfo;
use App\Traits\GMPCustomerBalanceTrait;
use App\Traits\NotificationTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    use GMPCustomerBalanceTrait, NotificationTrait;

    public function index()
    {
        $user=auth()->user();
        $result = Shipment::with('customer')->where('gmpid', $user->gmpid)->where('p_status', '1');
        if (request()->input("parcelid") != null) {
            $orderid=request()->input("parcelid");
            $result->where('orderid', $orderid);
        }
        if (request()->input("trackingid") != null) {
            $trackingid=request()->input("trackingid");
            $result->where('trackingid', $trackingid);
        }
        if (request()->input("cphone") != null) {
            $cphone=request()->input("cphone");
            $result->where('cphone', $cphone);
        }
        if (request()->input("rphone") != null) {
            $rphone=request()->input("rphone");
            $result->where('rphone', $rphone);
        }
        if (request()->input("status") != null) {
            $status=request()->input("status");
            switch($status) {
              case '1':
                $result->where('status', '0');
                break;
              case '2':
                $result->where('status', '100');
                break;
              case '3':
                $result->where(function($query) {
                    $query->where('status', '200')->orWhere('status', '300');
                });
                break;
              case '4':
                $result->where(function($query) {
                    $query->where('status', '400')->orWhere('status', '500');
                });
                break;
              case '5':
                $result->where('status', '1');
                break;
              case '6':
                $result->where('status', '4');
                break;
            }
          }
        if (request()->input("stopdate") != null) {
            if (request()->input("startdate") == null) {
                return response()->json(["message" => "start-date required if end-date given.", "status" => "error"], 400);
            }
            $startdate= date('Y-m-d',strtotime(request()->input("startdate")));
            $enddate=date('Y-m-d',strtotime(request()->input("stopdate")));
            $result->whereBetween('created_at', [$startdate, $enddate]);
        }elseif(request()->input("startdate") != null){
            $startdate= date('Y-m-d',strtotime(request()->input("startdate")));
            $enddate=Carbon::tomorrow()->format('Y-m-d');
            $result->whereBetween('created_at', [$startdate, $enddate]);
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
            $perPage=100;
        }
        $shipments=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($shipments, 200);
    }

    public function fetchrecent(Request $request)
    {
        $user=auth()->user();
        $gmpid=$user->gmpid;
        $getrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL').'/api/lists/recentshipmentlist', [
            "gmpid"=>$gmpid,
        ]);
        $res=$getrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "status" => "error"], 400);
            }else{
                $res_arr=$res['data'];
                $output['data']=$res_arr;
                $output['message']="Fetched Successfully";

                return response()->json($output, 201);
            }
        }
    }

    public function getshipment(Request $request)
    {
        $getrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL').'/api/shipment/getshipmentdetails', [
            "id"=>$request->shipmentid,
        ]);
        $res=$getrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "status" => "error"], 400);
            }else{
                return response()->json($res, 200);
            }
        }
    }

    public function track(Request $request)
    {
        $shipment=Shipment::where('trackingid', $request->trackingno)->where('deleted', '1')->where('status', '4')->first();
        if (!$shipment) {
            return response()->json(["message" => "Tracking No does not Exist", "status" => "error"], 400);
        }
        $shipment = @new TrackShipmentResource($shipment);
        $response=[
            "message" => "Fetched successfully",
            'data' => $shipment,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function getquote(GetInterStateQuoteRequest $request)
    {
        $quantity=[];
        for ($i=0; $i < count($request->json('itemtype')); $i++) {
            if ($request->itemtype[$i]=='2') {
                if (empty($request->item[$i])) {
                    return response()->json(["message" => "A Special Item was not selected", "status" => "error"], 400);
                }
            }
            array_push($quantity, "1");
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
            "lat"=>$request->latitude,
            "lng"=>$request->longitude,
            "itemtype"=>serialize($request->itemtype),
            "sitem"=>serialize($request->item),
            "itemquantity"=>serialize($request->itemquantity),
            "itemweight"=>serialize($request->itemweight),
            "itemvalue"=>serialize($request->itemvalue)
        ]);
        $res=$createrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while getting quote", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "amount"=>$res['amount'], "status" => "error"], 400);
            }else{
                //return response()->json($res, 201);
                // $response=[
                //     "message" => $res['message'],
                //     "amount"=>100,
                //     // "amount"=>$res['amount'],
                //     "status" => "success"
                // ];
                $response=[
                    "message" => "Quote Generated",
                    'amount' => strval($res['amount']),
                    "delivery_time"=> intval($res['delivery_timeline']['timelineduration']),
                    "status" => "success"
                ];
                return response()->json($response, 201);
            }
        }
    }

    public function callToGetQuote($pickupvehicle, $deliverymode, $pickupcenter, $sourceregion, $destinationregion, $latitude, $longitude, $itemtype, $quantity, $item, $itemweight, $itemvalue) {
        $createrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL').'/api/shipment/getquote', [
            "pickupvehicle"=>$pickupvehicle,
            "deliverymode"=>$deliverymode,
            "pickupcenter"=>$pickupcenter,
            "sourceregion"=>$sourceregion,
            "destinationregion"=>$destinationregion,
            "lat"=>$latitude,
            "lng"=>$longitude,
            "itemtype"=>serialize($itemtype),
            "sitem"=>serialize($item),
            "itemquantity"=>serialize($quantity),
            "itemweight"=>serialize($itemweight),
            "itemvalue"=>serialize($itemvalue)
        ]);
        return $createrequest->json();
    }

    public function store(CreateInterStateShipmentRequest $request)
    {
        $user=auth()->user();
        $quantity=[];
        for ($i=0; $i < count($request->itemtype); $i++) {
            if (!is_numeric($request->itemvalue[$i])) {
                return response()->json(["message" => "Some Item Value is invalid", "status" => "error"], 400);
            }
            if (!is_numeric($request->itemweight[$i]) && !is_float($request->itemweight[$i])) {
                return response()->json(["message" => "Some Item Weight is invalid", "status" => "error"], 400);
            }
            if ($request->itemtype[$i]=='2') {
                if (empty($request->item[$i])) {
                    return response()->json(["message" => "A Special Item was not selected", "status" => "error"], 400);
                }
            }elseif ($request->itemtype[$i]=='1') {
                if (empty($request->itemname[$i])) {
                    return response()->json(["message" => "Item Name is Required", "status" => "error"], 400);
                }
            }
            array_push($quantity, "1");
        }

        // $res=$this->callToGetQuote($request->pickupvehicle, $request->deliverymode, $request->pickupcenter,
        // $request->sourceregion, $request->destinationregion,
        // $request->latitude, $request->longitude, $request->itemtype,
        // $quantity, $request->item, $request->itemweight, $request->itemvalue);
        // if (!$res['status']) {
        //     return response()->json(["message" => "An Error occurred while balancing quote", "status" => "error"], 400);
        // }else{
        //     if ($res['status']=="error") {
        //         return response()->json(["message" => $res['message'], "amount"=>$res['amount'], "status" => "error"], 400);
        //     }else{
        //         $tamount = strval($res['amount']);
        //     }
        // }
        //$tamount=$request->totalamount;
        $tamount='100';


        $user=auth()->user();
        if ($request->gmppayment=='1') {
            if(!$this->checkWallet($tamount)){
                return response()->json(["message" => "Insuficient Funds", "status" => "error"], 400);
            }
            $p_status='1';

            //////////////
            //Charge wallet and Insert
            ///////////////////
            $this->chargeWallet($tamount);
            $logistics = Shipment::create([
                "entity_guid"=>Str::uuid(),
                "pickupvehicle"=>$request->pickupvehicle,
                "gmpid"=>$user->gmpid,
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
                "amount_collected"=>$tamount,
                "branch"=>$this->getFirstBranchByRegion($request->sourceregion),
                "rbranch"=>($request->deliverymode=='2') ? $this->getFirstBranchByRegion($request->sourceregion) : $request->pickupcenter,
                "collection_time"=>time(),
                "fromgmp"=>'1',
                "fromcountry"=>"1",
                "paymenttype"=>"1",
                "paymentmethod"=>"2",
                "mot"=>"2",
                "client_type"=>"0",
                "cod"=>"2",
                "cod_amount"=>"0",
                "trackingid"=>$this->getTrackingNO(),
                "orderid"=>$this->getDeliveryNO(),
            ]);
            for ($i=0; $i < count($request->itemtype); $i++) {
                ShipmentInfo::create([
                    "entity_guid"=>Str::uuid(),
                    "shipment_id"=>$logistics->id,
                    "type"=>$request->itemtype[$i],
                    "item"=>$request->item[$i],
                    "name"=>$request->itemname[$i],
                    "weight"=>$request->itemweight[$i],
                    "quantity"=>$request->itemquantity[$i],
                    "weighttype"=>'1',
                    "length"=>'1',
                    "width"=>'1',
                    "height"=>'1',
                    "value_declaration"=>$request->itemvalue[$i]
                ]);
            }
            FundingHistory::create([
                'fundingid' => $logistics->id,
                'gmpid' => $logistics->gmpid,
                'amount'=>$tamount,
                'ftime'=>time(),
                'currency'=>'NGN',
                'status'=>'1',
                'type'=>'2',
                'which'=>'2'
            ]);
            $this->NotifyMe("Wallet Charged for Logistics", "You have been charged ".$tamount, "2", "2");

            $this->NotifyMe("Logistics Booked", $logistics->trackingid, "3", "2");
            $details = [
                'trackingid'=>$logistics->trackingid,
                'orderid'=>$logistics->orderid,
                'rphone'=>'234'.substr($logistics->rphone, 0),
                'cphone'=>'234'.substr($logistics->cphone, 0),
            ];
            try {
                dispatch(new SendTrackingNoJob($details))->delay(now()->addSeconds(1));
            } catch (\Throwable $e) {
                report($e);
                Log::error('Error in sending sms: '.$e->getMessage());
            }

            $response=[
                "message" => "Shipment Created Successfully",
                'data' => $logistics,
                "status" => "success"
            ];
            return response()->json($response, 201);

        }else{
            $p_status='0';
            $logisticid="GMPLOG".time();
            $logistics = Logistic::create([
                "logisticid"=>$logisticid,
                "pickupvehicle"=>$request->pickupvehicle,
                "gmpid"=>$user->gmpid,
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
                "amount"=>$tamount,
            ]);
            for ($i=0; $i < count($request->itemtype); $i++) {
                LogisticInfo::create([
                    "shipment_id"=>$logistics->id,
                    "type"=>$request->itemtype[$i],
                    "item"=>$request->item[$i],
                    "name"=>$request->itemname[$i],
                    "weight"=>$request->itemweight[$i],
                    "quantity"=>$request->itemquantity[$i],
                    "weighttype"=>'1',
                    "length"=>'1',
                    "width"=>'1',
                    "height"=>'1',
                    "value_declaration"=>$request->itemvalue[$i]
                ]);
            }
            ///Pay with payment gateway
            $useragent=$_SERVER['HTTP_USER_AGENT'];
            // $pamount=(int)$amount*100;
            $pamount=(int)$tamount;
            $paymentrequest = Http::withHeaders([
                "Authorization" => "Bearer ".env('FW_KEY'),
                "content-type" => "application/json",
                "Cache-Control" => "no-cache",
                "User-Agent" => $useragent,
            ])->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $logisticid,
                'amount' => $pamount,
                'currency' => 'NGN',
                'redirect_url' => ($request->payfrom=="2") ? 'http://localhost:5173/verifypaymentlogistics' : 'https://gavice.com/gmp-payment',
                "customer" => [
                    'email' => $user->email,
                ],
                "customizations" => [
                    "title" => "Gavice Market Place",
                    "logo" => "https://gavice.com/small-logo.jpg"
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
            if ($tx->p_status=="1") {
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
                    $logisticsinfo=json_decode(json_encode($logisticsinfo), true);
                    //print_r($svalue); exit();
                    $shipment = Shipment::create([
                        "entity_guid"=>Str::uuid(),
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
                        "fromregion"=>$logistics->sourceregion,
                        "toregion"=>$logistics->destinationregion,
                        "totalweight"=>$logistics->totalweight,
                        "amount_collected"=>$logistics->amount,
                        "branch"=>$this->getFirstBranchByRegion($logistics->sourceregion),
                        "rbranch"=>($logistics->deliverymode=='2') ? $this->getFirstBranchByRegion($logistics->sourceregion) : $logistics->pickupcenter,
                        "collection_time"=>time(),
                        "fromgmp"=>'1',
                        "fromcountry"=>"1",
                        "paymenttype"=>"1",
                        "paymentmethod"=>"2",
                        "mot"=>"2",
                        "client_type"=>"0",
                        "cod"=>"2",
                        "cod_amount"=>"0",
                        "trackingid"=>$this->getTrackingNO(),
                        "orderid"=>$this->getDeliveryNO(),
                    ]);
                    foreach ($logisticsinfo as $l) {
                        $l = (object) $l;
                        ShipmentInfo::create([
                            "entity_guid"=>Str::uuid(),
                            "shipment_id"=>$shipment->id,
                            "type"=>$l->type,
                            "item"=>$l->item,
                            "name"=>$l->name,
                            "weight"=>$l->weight,
                            "quantity"=>$l->quantity,
                            "weighttype"=>$l->weighttype,
                            "length"=>$l->length,
                            "width"=>$l->width,
                            "height"=>$l->height,
                            "value_declaration"=>$l->value_declaration
                        ]);
                    }
                    $this->NotifyMe("Logistics Booked", $shipment->trackingid, "3", "2");
                    $details = [
                        'trackingid'=>$shipment->trackingid,
                        'orderid'=>$shipment->orderid,
                        'rphone'=>'234'.substr($shipment->rphone, 0),
                        'cphone'=>'234'.substr($shipment->cphone, 0),
                    ];
                    try {
                        dispatch(new SendTrackingNoJob($details))->delay(now()->addSeconds(1));
                    } catch (\Throwable $e) {
                        report($e);
                        Log::error('Error in sending sms: '.$e->getMessage());
                    }
                    $response=[
                        "message" => "Shipment Created Successfully",
                        'data' => $shipment,
                        "status" => "success"
                    ];
                    return response()->json($response, 201);

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

    public function getTrackingNO() {
        $i=0;
        while ( $i==0) {
          $trackingid=rand(10000000, 99999999);
          $query1 = DB::table('shipment')->select('trackingid')->where('trackingid', $trackingid);
          $query2 = DB::table('haulages')->select('trackingid')->where('trackingid', $trackingid);
          $countshipment = $query1->union($query2)->count();
          if ($countshipment<1) {
            $i=1;
          }
        }
        return $trackingid;
    }
    public function getDeliveryNO() {
        $i=0;
        while ( $i==0) {
          $orderid=$this->generateRandomString(8);
          $query1 = DB::table('shipment')->select('orderid')->where('orderid', $orderid);
          $query2 = DB::table('haulages')->select('orderid')->where('orderid', $orderid);
          $countshipment = $query1->union($query2)->count();
          if ($countshipment<1) {
            $i=1;
          }
        }
        return $orderid;
    }

    public function generateRandomString($length = 25) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getFirstBranchByRegion($region) {
        return PickupCenter::where('state', $region)->first()->id;
    }


}
