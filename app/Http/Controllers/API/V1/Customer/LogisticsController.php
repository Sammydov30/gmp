<?php

namespace App\Http\Controllers\API\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateInterStateShipmentRequest;
use App\Http\Requests\Customer\GetInterStateQuoteRequest;
use App\Jobs\Customer\SendTrackingNoJob;
use App\Models\Customer;
use App\Models\FundingHistory;
use App\Models\Logistic;
use App\Models\LogisticInfo;
use App\Traits\GMPCustomerBalanceTrait;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LogisticsController extends Controller
{
    use GMPCustomerBalanceTrait, NotificationTrait;

    public function index(Request $request)
    {
        $user=auth()->user();
        $pageno=(isset($request->page)) ? $request->page : 1;
        $perpage=(isset($request->per_page)) ? $request->per_page : 20; $perpage=intval($perpage);
        $gmpid=$user->gmpid;
        $parcelid=(!empty($request->parcelid)) ? $request->parcelid : '';
        $trackingid=(!empty($request->trackingid)) ? $request->trackingid : '';
        $status=(!empty($request->status)) ? $request->status : '';
        $cphone=(!empty($request->cphone)) ? $request->cphone : '';
        $rphone=(!empty($request->rphone)) ? $request->rphone : '';
        $startdate=(!empty($request->startdate)) ? $request->startdate : '';
        $enddate=(!empty($request->enddate)) ? $request->enddate: '';

        $getrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL').'/api/lists/shipmentlist', [
            "parcelid"=>$parcelid,
            "trackingid"=>$trackingid,
            "gmpid"=>$gmpid,
            "status"=>$status,
            "cphone"=>$cphone,
            "rphone"=>$rphone,
            "startdate"=>$startdate,
            "enddate"=>$enddate,
            "per_page"=>$perpage,
            "page"=>$pageno,
        ]);
        $res=$getrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "status" => "error"], 400);
            }else{

                $url=env('APP_URL').'/api/v1/customer/logistics/fetchall?trackingid='.$trackingid.'&parcelid='.$parcelid.'&status='.$status.'&cphone='.$cphone.'&rphone='.$rphone.'&startdate='.$startdate.'&enddate='.$enddate.'&per_page='.$perpage;
                $total_rows=$res['total'];
                $total_pages=$res['last_page'];
                $res_arr=$res['data'];
                $from=($perpage*$pageno)-($perpage-1);
                $to=(($perpage*$pageno)>$total_rows) ? $total_rows : ($perpage*$pageno);
                $prevpage=$pageno-1;
                $nextpage=$pageno+1;
                if ($total_pages>0) {
                  $output['data']=$res_arr;
                  $output['total']=$total_rows;
                  $output['current_page']=$output['page']=$pageno;
                  $output['per_page']=$perpage;
                  $output['from']=$from;
                  $output['to']=$to;
                  $output['last_page']=$total_pages;
                  $output['first_page_url']=$url.'&page=1';
                  $output['last_page_url']=$url.'&page='.$total_pages;
                  $output['prev_page_url']=($prevpage>=1) ? $url.'&page='.$prevpage : null;
                  $output['next_page_url']=($nextpage<$total_pages) ? $url.'&page='.$nextpage : null;
                  $output['path']=$url;

                  //Form Links
                  /////////////////////
                  ///////////////

                  $total_links = $total_pages;
                  $page=$pageno;
                  //echo $total_links;
                  if($total_links > 4){
                    if($page < 5){
                      for($count = 1; $count <= 5; $count++)
                      {
                        $page_array[] = $count;
                      }
                      $page_array[] = '...';
                      $page_array[] = $total_links;
                    }else{
                      $end_limit = $total_links - 5;
                      if($page > $end_limit){
                        $page_array[] = 1;
                        $page_array[] = '...';
                        for($count = $end_limit; $count <= $total_links; $count++){
                          $page_array[] = $count;
                        }
                      }else{
                        $page_array[] = 1;
                        $page_array[] = '...';
                        for($count = $page - 1; $count <= $page + 1; $count++){
                          $page_array[] = $count;
                        }
                        $page_array[] = '...';
                        $page_array[] = $total_links;
                      }
                    }
                  }else{
                    for($count = 1; $count <= $total_links; $count++){
                      $page_array[] = $count;
                    }
                  }

                  $links=[];
                  for($count = 0; $count < count($page_array); $count++){
                    $pgcount=($page_array[$count] == '...') ? '...' : $page_array[$count];
                    if($page == $page_array[$count]){
                      $linkdetails = array(
                        "url" => $url.'&page='.$pgcount,
                        "label" => $pgcount,
                        "active" => true,
                      );
                    }else{

                      if($page_array[$count] == '...'){
                        $linkdetails = array(
                          "url" => $pgcount,
                          "label" => $pgcount,
                          "active" => false,
                        );
                      }else{
                        $linkdetails = array(
                          "url" => $url.'&page='.$pgcount,
                          "label" => $pgcount,
                          "active" => true,
                        );
                      }

                    }

                    array_push($links, $linkdetails);
                  }
                  $output['links']=$links;
                }else{
                  //Empty Table
                  /////////////////
                  //////////
                  $output['data']=[];
                  $output['total']=0;
                  $output['per_page']=$perpage;
                  $output['current_page']=$output['from']=$output['to']=$output['last_page']=$output['first_page_url']=$output['last_page_url']=$output['prev_page_url']=$output['next_page_url']=null;
                  $output['path']=$url;
                }
                $output['message']="Fetched Successfully";

                return response()->json($output, 201);
            }
        }
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
        $getrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL').'/api/shipment/track', [
            "trackingno"=>$request->trackingno,
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

    public function store(CreateInterStateShipmentRequest $request)
    {
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
            "amount"=>$tamount,
        ]);
        for ($i=0; $i < count($request->itemtype); $i++) {
            LogisticInfo::create([
                "shipment_id"=>$logistics->id,
                "type"=>$request->itemtype[$i],
                "item"=>$request->item[$i],
                "name"=>$request->itemname[$i],
                "weight"=>$request->itemweight[$i],
                "weighttype"=>'1',
                "quantity"=>'1',
                "length"=>'1',
                "width"=>'1',
                "height"=>'1',
                "value_declaration"=>$request->itemvalue[$i]
            ]);
        }
        if ($request->gmppayment=='1') {
            $this->chargeWallet($tamount);
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
                "totalamount"=>$tamount,
                "stype"=>$request->itemtype,
                "sitem"=>$request->item,
                "sname"=>$request->itemname,
                "sweight"=>$request->itemweight,
                // "sweighttype"=>'1',
                // "squantity"=>'1',
                // "slength"=>'1',
                // "swidth"=>'1',
                // "sheight"=>'1',
                "svalue_declaration"=>$request->itemvalue
            ]);
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
            $res=$createrequest->json();
            //print_r($res); exit();
            if (!$res['status']) {
                return response()->json(["message" => "An Error occurred while creating record", "status" => "error"], 400);
            }else{
                if ($res['status']=="error") {
                    return response()->json(["message" => $res['message'], "status" => "error"], 400);
                }else{
                    Logistic::where("id", $logistics->id)->update([
                        "trackingid"=>$res['data']['trackingid'],
                        "orderid"=>$res['data']['orderid'],
                    ]);
                    $this->NotifyMe("Logistics Booked", $res['data']['trackingid'], "3", "2");
                    $details = [
                        'trackingid'=>$res['data']['trackingid'],
                        'orderid'=>$res['data']['orderid'],
                        'rphone'=>'234'.substr($logistics->rphone, 0),
                        'cphone'=>'234'.substr($logistics->cphone, 0),
                    ];
                    try {
                        dispatch(new SendTrackingNoJob($details))->delay(now()->addSeconds(1));
                    } catch (\Throwable $e) {
                        report($e);
                        Log::error('Error in sending sms: '.$e->getMessage());
                    }
                    return response()->json($res, 201);
                }
            }

        }else{
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
                'redirect_url' => 'https://gavice.com/gmp-payment',
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
            "itemquantity"=>serialize($quantity),
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
                    $logisticsinfo=json_decode(json_encode($logisticsinfo), true);
                    $stype=$sitem=$sname=$sweighttype=$sweight=$squantity=$slength=$swidth=$sheight=$svalue=[];

                    foreach ($logisticsinfo as $l) {
                        //$cart[$c]['item']['vendorname']=$this->getvendorname($v['item']['vendor']);
                        array_push($stype, $l['type']);array_push($svalue, $l['value_declaration']);
                        array_push($sitem, $l['item']);array_push($sname, $l['name']);
                        array_push($sweighttype, $l['weighttype']);array_push($sweight, $l['weight']);
                        array_push($squantity, $l['quantity']);array_push($slength, $l['length']);
                        array_push($swidth, $l['width']);array_push($sheight, $l['height']);
                    }
                    //print_r($svalue); exit();
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
                        "stype"=>$stype,
                        "sitem"=>$sitem,
                        "sname"=>$sname,
                        "sweighttype"=>$sweighttype,
                        "sweight"=>$sweight,
                        "squantity"=>$squantity,
                        "slength"=>$slength,
                        "swidth"=>$swidth,
                        "sheight"=>$sheight,
                        "svalue_declaration"=>$svalue
                    ]);
                    $res=$createrequest->json();
                    if (!$res['status']) {
                        return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
                    }else{
                        if ($res['status']=="error") {
                            return response()->json(["message" => $res['message'], "status" => "error"], 400);
                        }else{
                            $this->NotifyMe("Logistics Booked", $res['data']['trackingid'], "3", "2");
                            Logistic::where("id", $logistics->id)->update([
                                "trackingid"=>$res['data']['trackingid'],
                                "orderid"=>$res['data']['orderid'],
                            ]);
                            $details = [
                                'trackingid'=>$res['data']['trackingid'],
                                'orderid'=>$res['data']['orderid'],
                                'rphone'=>'234'.substr($logistics->rphone, 0),
                                'cphone'=>'234'.substr($logistics->cphone, 0),
                            ];
                            try {
                                dispatch(new SendTrackingNoJob($details))->delay(now()->addSeconds(1));
                            } catch (\Throwable $e) {
                                report($e);
                                Log::error('Error in sending sms: '.$e->getMessage());
                            }
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
