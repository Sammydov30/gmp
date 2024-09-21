<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateInterStateShipmentRequestTP;
use App\Http\Requests\GetQuoteRequest;
use App\Http\Resources\API\V1\Customer\TrackShipmentResource2;
use App\Jobs\TPEmailJob;
use App\Jobs\TPSMSJob;
use App\Models\ActivationValue;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        ])->get(env('SOLVENT_BASE_URL_LIVE').'/api/shipment/getquote', [
            // "pickupvehicle"=>$request->pickupvehicle,
            // "deliverymode"=>$request->deliverymode,
            // "pickupcenter"=>$request->pickupcenter,
            "pickupvehicle"=>'3',
            "deliverymode"=>'1',
            "pickupcenter"=>'1',
            "sourceregion"=>$request->sourceregion,
            "destinationregion"=>$request->destinationregion,
            "lat"=>$request->latitude,
            "lng"=>$request->longitude,
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


        $logistics = Shipment::create([
            "entity_guid"=>Str::uuid(),
            "pickupvehicle"=>$request->pickupvehicle,
            "gmpid"=>$request->userid,
            "pickupdate"=>$request->pickupdate,
            "gmppayment"=>"3",
            "p_status"=>"1",
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
            "amount_collected"=>$request->totalamount,
            "branch"=>$this->getFirstBranchByRegion($request->sourceregion),
            "rbranch"=>($request->deliverymode=='2') ? $this->getFirstBranchByRegion($request->sourceregion) : $request->pickupcenter,
            "collection_time"=>time(),
            "fromgmp"=>'2',
            "fromcountry"=>"1",
            "paymenttype"=>"1",
            "paymentmethod"=>"2",
            "mot"=>"2",
            "client_type"=>"0",
            "cod"=>"2",
            "cod_amount"=>"0",
            "solventapproved"=>'0',
            "newest"=>'1',
            "type"=>'1',
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

        //$this->NotifyMe("Logistics Booked", $res['data']['trackingid'], "3", "2");
        $details = [
            'trackingid'=>$logistics->trackingid,
            'orderid'=>$logistics->orderid,
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
            'trackingid'=>$logistics->trackingid,
            'orderid'=>$logistics->orderid,
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
        $shipment=Shipment::where('id', $logistics->id)->first();
        if (!$shipment) {
            return response()->json(["message" => "Tracking No does not Exist", "status" => "error"], 400);
        }
        $shipment = @new TrackShipmentResource2($shipment);
        $response=[
            "message" => "Shipment Created Successfully",
            'data' => $logistics,
            "status" => "success"
        ];
        return response()->json($response, 201);


    }

    public function trackfor3p(Request $request)
    {
        $shipment=Shipment::with('shipmentinfo')->where('trackingid', $request->trackingno)->where('deleted', '0')->where('status', '!=', '4')->where('type', '1')->first();
        if (!$shipment) {
            return response()->json(["message" => "Tracking No does not Exist", "status" => "error"], 400);
        }
        $shipment = @new TrackShipmentResource2($shipment);
        $response=[
            "message" => "Fetched successfully",
            'data' => $shipment,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getirspricelist(Request $request)
    {
        $createrequest = Http::withHeaders([
            "content-type" => "application/json",
        ])->get(env('SOLVENT_BASE_URL_LIVE').'/api/lists/getirspricelist', [
            "userid"=>$request->userid,
        ]);
        $res=$createrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred", "status" => "error"], 400);
        }else{
            if ($res['status']=="error") {
                return response()->json(["message" => $res['message'], "status" => "error"], 400);
            }else{
                return response()->json($res, 201);
            }
        }
    }

    public function CountSAppointments($year)
    {
        $doctor=auth()->user();
        $appointmentdata=[];
        $monthlyCounts = DB::table('appointments')
        ->select(DB::raw('count(*) as count_s, month(created_at) as month'))
        ->where('doctorid', $doctor->doctorid)
        ->whereIn('status', ['1', '2', '3'])->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')->pluck('count_s', 'month');
        $arraydata= json_decode(json_encode($monthlyCounts), 2);
        for ($i=1; $i <= 12; $i++) {
            if (isset($arraydata[$i])) {
                array_push($appointmentdata, $arraydata[$i]);
            }else{ array_push($appointmentdata, 0); }
        }
        return $appointmentdata;
    }

    public function countItems(Request $request)
    {
        $user=auth()->user();
        switch ($request->period) {
            case 'this-week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'last-week':
                $start = Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY);
                $end = Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY);
                break;
            case 'last-month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this-quarter':
                $start = Carbon::now()->firstOfQuarter();
                $end = Carbon::now()->lastOfQuarter();
                break;
            case 'last-quarter':
                $start = Carbon::now()->subQuarter()->firstOfQuarter();
                $end = Carbon::now()->subQuarter()->lastOfQuarter();
                break;
            case 'this-year':
                $start = Carbon::now()->firstOfYear();
                $end = Carbon::now()->lastOfYear();
                break;
            case 'last-year':
                $start = Carbon::now()->subYear()->firstOfYear();
                $end = Carbon::now()->subYear()->lastOfYear();
                break;
            default:
                $start = Carbon::today();
                $end = Carbon::today();
                break;
        }

        // Fetch the count of items added this week
        $newItemsCount = Product::where('gmpid', $user->gmpid)->whereBetween('created_at', [$start, $end])->count();
        $ItemsSoldCount = Order::where('p_status', '1')->where('customer', $user->gmpid)->whereBetween('created_at', [$start, $end])->count();

        $response=[
            "message" => "Successful",
            'data' => ['no_of_item_added'=>$newItemsCount, 'no_of_item_sold'=>0],
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function runquery()
    {
        $orders=Order::all();

        foreach ($orders as $order) {
            $pqs=@explode(",", $order->products);
            for ($i=0; $i < count($pqs); $i++) {
                $pq=explode("|", $pqs[$i]);
                $product=Product::where('id', $pq[0])->first();
                OrderItem::created([
                    'orderid' => $order->orderid,
                    'customer' => $order->customer,
                    'storeid'=>$order->storeid,
                    'sellerid'=>$order->sellerid,
                    'product' => $product->id,
                    'quantity'=>$pq[1],
                    'unitcost'=>$order->amount,
                ]);
            }
        }
        return "All Done";
    }

}
