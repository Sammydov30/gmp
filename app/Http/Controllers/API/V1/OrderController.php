<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Customer\ProductResource;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Product;
use App\Models\Region;
use App\Models\Rider;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $time=date('h:i:s a');
        if ($status=="1") {
            Order::where('orderid', $orderId)->update(['status'=>$status, 'readytime'=>$time]);
        } else if ($status=="2") {
            Order::where('orderid', $orderId)->update(['status'=>$status, 'acceptedtime'=>$time]);
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
        $response=$this->UpdateDStatus($request->orderId, '1');
        return response()->json($response, 200);
    }
    public function markAccepted(Request $request){
        $rider=auth()->user();
        $response=$this->UpdateDStatus($request->orderId, '2');
        return response()->json($response, 200);
    }
    public function markPickedUp(Request $request){
        $response=$this->UpdateDStatus($request->orderId, '3');
        return response()->json($response, 200);
    }
    public function markDelivered(Request $request){
        $rider=auth()->user();
        $response=$this->UpdateDStatus($request->orderId, '4');
        $log=Order::where('orderid', $request->orderId)->first();
        $location=$log->region;
        $this->updateEarning($rider->riderid);
        $howmany=$rider->howmany-1;
        return response()->json($response, 200);
    }
    public function markCancelled(Request $request){
        $response=$this->UpdateDStatus($request->orderId, '5');
        return response()->json($response, 200);
    }

}

