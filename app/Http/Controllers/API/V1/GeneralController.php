<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateInterStateShipmentRequestTP;
use App\Http\Requests\GetQuoteRequest;
use App\Jobs\Admin\TPEmailJob;
use App\Jobs\Customer\TPSMSJob;
use App\Models\ActivationValue;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeneralController extends Controller
{
    public function getquote(GetQuoteRequest $request)
    {

        // $response=[
        //     "message" => "Quote Generated",
        //     'quote' => '3500',
        //     "status" => "success"
        // ];
        // return response()->json($response, 200);
        $itemtype=["1"];
        $sitem=["1"];
        // $sitem=(is_array($request->item)) ? $request->item : ["1"];
        $quantity=(is_array($request->quantity)) ? $request->itemquantity : [$request->itemquantity];
        $itemweight=(is_array($request->itemweight)) ? $request->itemweight : [$request->itemweight];
        $itemvalue=(is_array($request->itemvalue)) ? $request->itemvalue : [$request->itemvalue];
        $createrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL2').'/api/shipment/getquote', [
            // "pickupvehicle"=>$request->pickupvehicle,
            // "deliverymode"=>$request->deliverymode,
            // "pickupcenter"=>$request->pickupcenter,
            "pickupvehicle"=>'3',
            "deliverymode"=>'1',
            "pickupcenter"=>'1',
            "sourceregion"=>$request->sourceregion,
            "destinationregion"=>$request->destinationregion,
            "itemtype"=>serialize($itemtype),
            "sitem"=>serialize($sitem),
            "itemquantity"=>serialize($quantity),
            "itemweight"=>serialize($itemweight),
            "itemvalue"=>serialize($itemvalue)
        ]);
        $res=$createrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "status" => "error"], 400);
            }else{
                //return response()->json($res, 201);
                $response=[
                    "message" => "Quote Generated",
                    'quote' => strval($res['amount']),
                    "delivery_time"=> intval($res['delivery_timeline']['timelineduration']),
                    "status" => "success"
                ];
                return response()->json($response, 200);
            }
        }
    }
    public function fetchvehicles()
    {
        $result = DB::table('pickupvehicle')->select('type', 'name', 'description', 'max_weight');
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

        $data=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($data, 200);
    }
    public function getsubamount()
    {
        $amount=ActivationValue::first()->subscriptionamount;
        $response=[
            "message" => "Fetched",
            'subscription_amount' => $amount,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function createshipmentfor3p(CreateInterStateShipmentRequestTP $request)
    {
        for ($i=0; $i < count($request->itemtype); $i++) {
            if ($request->itemtype[$i]=='2') {
                if (empty($request->item[$i])) {
                    return response()->json(["message" => "A Special Item was not selected", "status" => "error"], 400);
                }
            }elseif ($request->itemtype[$i]=='1') {
                if (empty($request->itemname[$i])) {
                    return response()->json(["message" => "Item Name is Required", "status" => "error"], 400);
                }
            }else{
                return;
            }
        }

        $logisticid="GMPLOG".time();

        $createrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->post(env('SOLVENT_BASE_URL2').'/api/shipment/createshipmentfortp', [
            "pickupvehicle"=>$request->pickupvehicle,
            "gmpid"=>$request->userid,
            "pickupdate"=>$request->pickupdate,
            "gmppayment"=>$request->gmppayment,
            "p_status"=>'1',
            "deliverymode"=>$request->deliverymode,
            "pickupcenter"=>$request->pickupcenter,
            "cname"=>$request->customername,
            "cphone"=>$request->customerphone,
            "caddress"=>$request->customeraddress,
            "rname"=>$request->recipientname,
            "rphone"=>$request->recipientphone,
            "raddress"=>$request->recipientaddress,
            "sourceregion"=>$request->sourceregion,
            "destinationregion"=>$request->destinationregion,
            "totalweight"=>$request->totalweight,
            "totalamount"=>$request->totalamount,
            "stype"=>$request->itemtype,
            "sitem"=>($request->item)?$request->item:'',
            "sname"=>$request->itemname,
            "sweight"=>$request->itemweight,
            // "sweighttype"=>'1',
            // "squantity"=>'1',
            // "slength"=>'1',
            // "swidth"=>'1',
            // "sheight"=>'1',
            "svalue_declaration"=>$request->itemvalue
        ]);
        $res=$createrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating record", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "status" => "error"], 400);
            }else{
                //$this->NotifyMe("Logistics Booked", $res['data']['trackingid'], "3", "2");
                $details = [
                    'trackingid'=>$res['data']['trackingid'],
                    'orderid'=>$res['data']['orderid'],
                    'email' => 'samydov@gmail.com',
                    'phone'=>'2347065975827',
                    'subject' => 'Gavice/Shipbubble',
                ];
                try {
                    dispatch(new TPSMSJob($details))->delay(now()->addSeconds(1));
                } catch (\Throwable $e) {
                    report($e);
                    Log::error('Error in sending: '.$e->getMessage());
                }
                try {
                    dispatch(new TPEmailJob($details))->delay(now()->addSeconds(1));
                } catch (\Throwable $e) {
                    report($e);
                    Log::error('Error in sending: '.$e->getMessage());
                }


                $details = [
                    'trackingid'=>$res['data']['trackingid'],
                    'orderid'=>$res['data']['orderid'],
                    'email' => 'akatobi.samuel@gmail.com',
                    'phone'=>'2348108655684',
                    'subject' => 'Gavice/Shipbubble',
                ];
                try {
                    dispatch(new TPSMSJob($details))->delay(now()->addSeconds(1));
                } catch (\Throwable $e) {
                    report($e);
                    Log::error('Error in sending: '.$e->getMessage());
                }
                try {
                    dispatch(new TPEmailJob($details))->delay(now()->addSeconds(1));
                } catch (\Throwable $e) {
                    report($e);
                    Log::error('Error in sending: '.$e->getMessage());
                }
                return response()->json($res, 201);
            }
        }

    }

    public function trackfor3p(Request $request)
    {
        $getrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL2').'/api/shipment/trackfor3p', [
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

    function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = 'km') {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.1515 * 1.609344;

        if ($unit == 'km') {
            return $kilometers;
        } else {
            return $kilometers * 0.621371;
        }

        // Example usage
        // $distance = calculateDistance(40.7128, -74.0060, 34.0522, -118.2437);
        // echo "Distance between New York and Los Angeles is: " . $distance . " kilometers";
    }

}
