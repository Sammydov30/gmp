<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetQuoteRequest;
use App\Models\ActivationValue;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
        ])->get(env('SOLVENT_BASE_URL').'/api/shipment/getquote', [
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
                return response()->json($res, 201);
            }
        }
    }
    public function fetchvehicles()
    {
        $result = DB::table('pickupvehicle')->select('type', 'name', 'description');
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
}
