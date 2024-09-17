<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Customer\ProductResource;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\PickupCenter;
use App\Models\Product;
use App\Models\Region;
use App\Models\Rider;
use App\Models\Shipment;
use App\Models\ShipmentInfo;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $user=auth()->user();
        $result = Order::with('customer')->where('customer', $user->gmpid)->where('p_status', '1');
        if (request()->input("orderid") != null) {
            $orderid=request()->input("orderid");
            $result->where('orderid', $orderid);
        }
        if (request()->input("ongoing") != null) {
            if (request()->input("ongoing")=='1') {
                $result->whereIn('status', ['0', '1', '2', '3']);
            }else{
                $result->whereIn('status', ['4']);
            }

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

        $order=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($order, 200);
    }
    private function GetCustomerName($customer){
        $customer=Customer::where('gmpid', $customer)->first();
        return @$customer->firstname.' '.@$customer->lastname;
    }
    private function GetRegionName($region){
        $region=Region::where('id', $region)->first();
        return @$region->name;
    }
    private function GetItemName($item){
        $item=Product::where('id', $item)->first();
        return @$item->name;
    }
    private function GetItemVendor($item){
        $item=Product::where('id', $item)->first();
        $store=Store::where('id', $item->storeid)->first()->name;
        return $store;
    }

    private function GetItemImg($item){
        $product=Product::with('market', 'store', 'productimages')->find($item);
        $product = @new ProductResource($product);
        //Convert to json then to array (To get Pure array)
        $item=json_decode(json_encode($product), true);
        //print_r($item); exit();
        return $item['img'];
    }
    private function GetVendorName($item){
        $item=Product::where('id', $item)->first();
        $store=Store::where('id', $item->storeid)->first()->name;
        return $store;
    }

    public function getSingleOrder(Request $request){
        $order = Order::where('id', $request->id)->first();
        if (!$order) {
            return response()->json(["message"=>"Request doesn't exist", "status"=>"error"], 400);
        }
        $order->customername=$this->GetCustomerName($order->customer);
        $order->regionname=$this->GetRegionName($order->region);
        $itemlist=array();
        $items=explode(",", $order->products);
        foreach ($items as $item => $value) {
            $pp=explode("|", $value);
            //print_r($pp); exit();
            $pt=[
                "itemname"=>@$this->GetItemName($pp[0]),
                "vendorname"=>@$this->GetVendorName($pp[0]),
                "quantity"=>$pp[1],
                // "imgg" => @$this->GetItemImg($pp[0]),
            ];
            array_push($itemlist, $pt);
        }
        $order->itemlist=$itemlist;

        $response=[
            "message" => "Fetched Successfully",
            "status" => "success",
            "details" => $order
        ];
        return response()->json($response, 200);
    }

    public function getOrdersforVendor()
    {
        $user=auth()->user();
        $result = DB::table('orders')->where('p_status', "1");
        if (request()->input("orderid") != null) {
            $orderid=request()->input("orderid");
            $result->where('orderid', $orderid);
        }
        if (request()->input("status")!=null) {
            $status= request()->input("status");
            $result->where('status', $status);
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

        $orders=$result->orderBY($sortBy, $sortOrder)->get();
        $neworders = array();
        foreach ($orders as $order => $value) {
            $items=$value->items;
            foreach ($items as $item) {
                $item=explode("|", $item);
                $p=$item[0];
                $orderitem = Product::where('id', $p)->first();
                if($orderitem->vendor==$user->id){
                    array_push($neworders, $order);
                }
            }
        }
        $response=[
            "message" => "Orders Fetched",
            'orders' => $neworders,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function getOrderItems(Request $request)
    {
        $order = Order::where('orderid', $request->orderid)->first();
        $orderitems = array();
        $items=$order->items;
        foreach ($items as $item) {
            $item=explode("|", $item);
            $p=$item[0];
            $orderitem = Product::where('id', $p)->first();
            array_push($orderitems, $orderitem);
        }
        $response=[
            "message" => "Items Fetched",
            'orderitems' => $orderitems,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }
    public function UpdateDStatus($orderId, $status){
        // Order::where('orderid', $orderId)->update(['status'=>$status]);
        date_default_timezone_set("Africa/Lagos");
        $time=date('d-m-Y h:ia');
        if ($status=="1") {
            Order::where('orderid', $orderId)->update(['status'=>$status, 'acceptedtime'=>$time]);
        } else if ($status=="2") {
            Order::where('orderid', $orderId)->update(['status'=>$status, 'readytime'=>$time]);
        }else if ($status=="3") {
            Order::where('orderid', $orderId)->update(['status'=>$status, 'pickuptime'=>$time]);
        }else if ($status=="4") {
            Order::where('orderid', $orderId)->update(['status'=>$status, 'deliverytime'=>$time]);
        }
        $response=[
            "message" => "Status Updated",
            "status" => "success"
        ];
        return $response;
    }
    public function markReady(Request $request){
        $order = Order::where('orderid', $request->orderid)->first();
        if (@$order->status!="1") {
            return response()->json(["message"=>"Cannot perform action", "status"=>"error"], 400);
        }

        if ($order->logisticsprovider=="1") {
            $buyer=Customer::where('gmpid', $order->customer)->first();
            $seller=Customer::where('gmpid', $order->sellerid)->first();

            $logistics = Shipment::create([
                "entity_guid"=>Str::uuid(),
                "pickupvehicle"=>"1",
                "gmpid"=>$order->customer,
                "pickupdate"=>date('d-m-Y'),
                "gmppayment"=>$order->paymentmethod,
                "p_status"=>"1",
                "deliverymode"=>$order->deliverymode,
                "cname"=>$seller->firstname. " ". $seller->lastname,
                "cphone"=>$seller->phone,
                "caddress"=>$seller->address." ".$seller->location,
                "rname"=>$buyer->firstname. " ". $buyer->lastname,
                "rphone"=>$buyer->phone,
                "raddress"=>$buyer->address." ".$buyer->location,
                "fromregion"=>$seller->region,
                "toregion"=>$buyer->region,
                "totalweight"=>$order->totalweight,
                "amount_collected"=>$order->servicefee,
                "branch"=>$this->getFirstBranchByRegion($seller->region),
                "rbranch"=>($order->deliverymode=='2') ? $this->getFirstBranchByRegion($buyer->region) : "1",
                "pickupcenter"=>($order->deliverymode=='2') ? $this->getFirstBranchByRegion($buyer->region) : "1",
                "collection_time"=>time(),
                "fromgmp"=>'1',
                "fromorderlist"=>'1',
                "gmporderid"=>$order->orderid,
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

            $prod_quants=explode(",", $order->products);
            foreach ($prod_quants as $one) {
                $ex=explode("|", $one);
                $product=$ex[0]; $quantity=$ex[1];
                $productdetails=Product::where('id', $product)->first();

                ShipmentInfo::create([
                    "entity_guid"=>Str::uuid(),
                    "shipment_id"=>$logistics->id,
                    "type"=>$productdetails->itemcat,
                    "item"=>$productdetails->packagetype,
                    "name"=>$productdetails->name,
                    "weight"=>$productdetails->weight,
                    "quantity"=>$quantity,
                    "weighttype"=>'1',
                    "length"=>$productdetails->length,
                    "width"=>$productdetails->width,
                    "height"=>$productdetails->height,
                    "value_declaration"=>$productdetails->amount
                ]);
            }

            $response=$this->UpdateDStatus($request->orderid, '2');
        } else {
            $response=$this->UpdateDStatus($request->orderid, '2');
            $response=$this->UpdateDStatus($request->orderid, '3');
            $response=$this->UpdateDStatus($request->orderid, '4');
        }

        return response()->json($response, 200);
    }
    public function markAccepted(Request $request){
        $rider=auth()->user();
        $response=$this->UpdateDStatus($request->orderid, '1');
        return response()->json($response, 200);
    }
    public function markPickedUp(Request $request){
        $response=$this->UpdateDStatus($request->orderid, '3');
        return response()->json($response, 200);
    }
    public function markDelivered(Request $request){
        $rider=auth()->user();
        $response=$this->UpdateDStatus($request->orderid, '4');
        $log=Order::where('orderid', $request->orderId)->first();
        $location=$log->region;
        $this->updateEarning($rider->riderid);
        $howmany=$rider->howmany-1;
        return response()->json($response, 200);
    }
    public function markCancelled(Request $request){
        $order=Order::where('orderid', $request->orderid)->first();
        if (@$order->status=="3" || @$order->status=="4") {
            return response()->json(["message"=>"Order Cannot be cancelled at this time.", "status"=>"error"], 400);
        }
        $response=$this->UpdateDStatus($request->orderid, '5');
        return response()->json($response, 200);
    }

    public function sellerorderlist()
    {
        $user=auth()->user();
        $result = Order::with('customer')->where('sellerid', $user->gmpid)->where('p_status', '1');
        if (request()->input("orderid") != null) {
            $orderid=request()->input("orderid");
            $result->where('orderid', $orderid);
        }
        if (request()->input("ongoing") != null) {
            if (request()->input("ongoing")=='1') {
                $result->whereIn('status', ['0', '1', '2', '3']);
            }else{
                $result->whereIn('status', ['4']);
            }

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

        $order=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($order, 200);
    }
    public function getFirstBranchByRegion($region) {
        return PickupCenter::where('state', $region)->first()->id;
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


}

