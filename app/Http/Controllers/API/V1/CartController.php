<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuyNowRequest;
use App\Http\Requests\MakeOrderRequest;
use App\Jobs\ConfirmAvailabilityJob;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FundingHistory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Traits\GMPCustomerBalanceTrait;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    use GMPCustomerBalanceTrait, NotificationTrait;

    public function index()
    {
        $user=auth()->user();
        $result = Cart::where('customer', $user->id);

        if (request()->input("item") != null) {
            $item=request()->input("item");
            $result->where('product', $item);
        }
        if (request()->input("availability") != null) {
            $availability=request()->input("availability");
            $result->where('availability', $availability);
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
        $cart=$result->orderBY($sortBy, $sortOrder)->get();

        $cstatus=$astatus=$totalitems=$totalamount=0;
        foreach ($cart as $c) {
            if($c['confirmed']!='1'){
                $cstatus='0';
                break;
            }
            $cstatus='1';

        }
        foreach ($cart as $c) {
            if($c['availability']!='1'){
                $astatus='0';
                break;
            }
            $astatus='1';

        }
        //Convert to json then to array (To get Pure array)
        $cart=json_decode(json_encode($cart), true);
        foreach ($cart as $c => $v) {
            $cart[$c]['item']['storename']=$this->getstorename($v['item']['storeid']);
            $totalitems=$totalitems+$v['quantity'];
            $totalamount=$totalamount+($v['item']['amount']*$v['quantity']);
        }

        return response()->json(["cartitems"=>$cart, "cstatus"=>$cstatus, "astatus"=>$astatus, "totalitems"=>$totalitems, "totalamount"=>$totalamount], 200);
    }
    public function cartsForVendorsGroup()
    {
        $user=auth()->user();

        $cart=DB::table('carts')
        ->leftjoin('customers','customers.id','=','carts.customer')
        ->leftjoin('products','products.id','=','carts.product')
        ->leftjoin('stores','stores.id','=','products.storeid')
        ->selectRaw('customers.id AS customerid, carts.id AS cartid,
        carts.confirmed, carts.availability,
        customers.firstname AS customername,
        customers.phone AS customerphone,
        stores.id AS storeid')
        ->where('customer.sellerid', $user->sellerid)
        ->groupByRaw('carts.id, carts.confirmed, carts.availability, customerid,
        customername, customerphone, storeid')
        ->get()->unique('customerid')->values()->all();

        $cstatus=$astatus=$totalitems=$totalamount=0;

        //Convert to json then to array (To get Pure array)
        $cart=json_decode(json_encode($cart), true);
        foreach ($cart as $c => $v) {
            $cart[$c]['confirmed']=($this->checkConfirmed($v['customerid'])) ? "1" : "0";;
        }

        return response()->json(["cartitems"=>$cart], 200);
    }
    private function checkConfirmed($customer) {
        $cart = Cart::where('customer', $customer)->get();
        foreach ($cart as $c) {
            if($c['confirmed']=='0'){
                return false;
            }
        }
        return true;
    }

    private function getstorename($store)
    {
        return Store::where('id', $store)->first()->name;
    }
    public function addtocart(Request $request)
    {
        $user=auth()->user();
        $checkitem=Product::find($request->item);
        if (!$checkitem) {
            return response()->json(["message" => "Item not available.", "status" => "error"], 400);
        }
        $query=Cart::where('product', $request->item)->where('customer', $user->id)->first();
        if ($query) {
            $cart = Cart::where('product', $request->item)->where('customer', $user->id)->update([
                'quantity' => $query->quantity+1,
            ]);
        }else{
            $query2=Cart::where('customer', $user->id)->first();
            if ($query2) {
                if ($query2->storeid!=$checkitem->storeid) {
                    return response()->json(["message" => "Item must be from the same store", "status" => "error"], 400);
                }
            }
            $cart = Cart::create([
                'product' => $request->item,
                'customer' => $user->id,
                'storeid'=> $checkitem->storeid,
                'quantity' => '1',
                'availability' => '1',
                'confirmed' => '1',
                // 'description'=> $request->description,
            ]);
        }
        $cartnum=Cart::where('customer', $user->id)->get();
        $tcartnum=0;
        foreach ($cartnum as $c) {
            $tcartnum=$tcartnum+$c['quantity'];
        }
        $response=[
            "totalcartnum" => $tcartnum,
            "message" => "Item Added to Cart",
            'cart' => $cart,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }
    public function addtocartgroup(Request $request)
    {
        $user=auth()->user();
        $cartnum=Cart::where('customer', $user->id)->delete();
        foreach ($request->itemlist as $item) {
            $checkitem=Product::find($item);
            if (!$checkitem) {
                return response()->json(["message" => "An Item not available.", "status" => "error"], 400);
            }
            $query=Cart::where('product', $item)->where('customer', $user->id)->first();
            if ($query) {
                $cart = Cart::where('product', $item)->where('customer', $user->id)->update([
                    'quantity' => $query->quantity+1,
                ]);
            }else{
                $query2=Cart::where('customer', $user->id)->first();
                if ($query2) {
                    if ($query2->storeid!=$checkitem->storeid) {
                        Cart::where('customer', $user->id)->delete();
                        return response()->json(["message" => "Items must be from the same store", "status" => "error"], 400);
                    }
                }
                Cart::create([
                    'product' => $item,
                    'customer' => $user->id,
                    'storeid'=> $checkitem->storeid,
                    'quantity' => '1',
                    'availability' => '1',
                    'confirmed' => '1',
                    // 'description'=> $request->description,
                ]);
            }
        }

        $cartnum=Cart::where('customer', $user->id)->get();
        $tcartnum=0;
        foreach ($cartnum as $c) {
            $tcartnum=$tcartnum+$c['quantity'];
        }
        $response=[
            "totalcartnum" => $tcartnum,
            "message" => "Item Added to Cart",
            'cart' => $cartnum,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function removefromcart(Request $request)
    {
        $user=auth()->user();
        $cart=Cart::where('id', $request->id);
        if ($cart->first()) {
            $cart->delete();
            $cartnum=Cart::where('customer', $user->id)->get();
            $tcartnum=0;
            foreach ($cartnum as $c) {
                $tcartnum=$tcartnum+$c['quantity'];
            }
            $response=[
                "totalcartnum" => $tcartnum,
                "message" => "Item removed from Cart",
                "status" => "success"
            ];
            return response()->json($response, 201);
        }else{
            $response=[
                "message" => "Item not found",
                "status" => "error"
            ];
            return response()->json($response, 400);
        }
    }

    public function increaseQuantity(Request $request)
    {
        $user=auth()->user();
        $query=Cart::where('id', $request->id)->first();
        if ($query) {
            $cart = Cart::where('id', $request->id)->update([
                'quantity' => $query->quantity+1,
                // 'confirmed' => '0',
                //'quantity' => $request->quantity,
            ]);
            $cartnum=Cart::where('customer', $user->id)->get();
            $tcartnum=0;
            foreach ($cartnum as $c) {
                $tcartnum=$tcartnum+$c['quantity'];
            }
            $response=[
                "totalcartnum" => $tcartnum,
                "message" => "Item Added to Cart",
                'cart' => $cart,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }else{
            $response=[
                "message" => "Item not found",
                "status" => "error"
            ];
            return response()->json($response, 400);
        }
    }
    public function decreaseQuantity(Request $request)
    {
        $user=auth()->user();
        $query=Cart::where('id', $request->id)->first();
        if ($query) {
            if($query->quantity > 1){
                $cart = Cart::where('id', $request->id)->update([
                    'quantity' => $query->quantity-1,
                    // 'confirmed' => '0',
                    //'quantity' => $request->quantity,
                ]);
                $cartnum=Cart::where('customer', $user->id)->get();
                $tcartnum=0;
                foreach ($cartnum as $c) {
                    $tcartnum=$tcartnum+$c['quantity'];
                }
                $response=[
                    "totalcartnum" => $tcartnum,
                    "message" => "Item removed from Cart",
                    'cart' => $cart,
                    "status" => "success"
                ];
                return response()->json($response, 201);
            }
            return response()->json(["message" => "Error adding to cart", "status" => "error"], 400);
        }else{
            $response=[
                "message" => "Item not found",
                "status" => "error"
            ];
            return response()->json($response, 400);
        }
    }

    public function getcartItems($user){
        $carts=Cart::where('customer', $user)->get();
        $items=array();
        $amount=$totalweight=0;
        foreach ($carts as $cart) {
            $storeid=$cart['storeid'];
            //$amount=$amount+$row['amount'];
            $item=$cart['product'].'|'.$cart['quantity'];
            array_push($items, $item);

            try {
                $product=Product::where('id', $cart['product'])->first();
                $vol_wgt = ($product->height * $product->width * $product->length) / 5000;
                if ($vol_wgt>$product->weight) {
                    $totalweight+=$vol_wgt;
                } else {
                    $totalweight+=$product->weight;
                }
            } catch (\Throwable $th) {
                continue;
            }
        }
        $items=implode(",", $items);
        $data=['items'=>$items, 'amount'=>$amount, 'storeid'=>$storeid, "totalweight"=>$totalweight];
        return $data;
    }
    public function checkcartItemsA($user){
        $carts=Cart::where('customer', $user)->get();
        $items=array();
        $amount=0;
        foreach ($carts as $c) {
            if($c['confirmed']!='1'){
                return false;
            }
            if($c['availability']!='1'){
                return false;
            }
        }
        return true;
    }
    public function checkcartItemsB($user){
        $cart=Cart::where('customer', $user)->latest('updated_at')->first();
        $regdate=strtotime($cart->updated_at);
        $currtime=time();
        $delay=$currtime - $regdate;
        if ($delay>900) {
            $this->unconfirmCart($user);
            return false;
        }
        return true;
    }
    function clearCart($user){
        $cart=Cart::where('customer', $user);
        if ($cart) {
            $cart->delete();
            return true;
        }
        return false;
    }
    function unconfirmCart($user){
        $cart=Cart::where('customer', $user);
        if ($cart) {
            $cart->update(['confirmed'=>'0', 'availability'=>'0']);
            return true;
        }
        return false;
    }
    public function checkout(Request $request)
    {
        $user=auth()->user();
        if(!$this->checkcartItemsA($user->id)){
            return response()->json(["message" => "Some items may not be available. Remove item(s) and try again", "status" => "error"], 400);
        }
        // if(!$this->checkcartItemsB($user->id)){
        //     return response()->json(["message" => "Please recheck the availability of the items", "status" => "error"], 400);
        // }
        return response()->json(["message" => "Operation Successful", "status" => "success"], 200);
    }

    public function getShippingRate (Request $request)
    {
        $user=auth()->user();
        $request->validate([
            'region' => 'required|integer',
            'logisticsprovider' => 'required|integer',
            'deliverymode' => 'required|integer'
        ]);
        $error=$output=array();
        // if (empty($request->region)) {
        //     array_push($error,"Region is Required");
        // }
        // if (empty($request->logisticsprovider)) {
        //     array_push($error," is Required");
        // }
        $cartnum=Cart::where('customer', $user->id)->count();
        if ($cartnum<1) {
            return response()->json(["message" => "Cart is Empty", "status" => "error"], 400);
        }
        if(!empty($error)){
            return response()->json(["message" => $error, "status" => "error"], 400);
        }

        $storeid = $this->getcartItems($user->id)['storeid'];
        $sellerid =Store::where('id', $storeid)->first()->gmpid;
        $sourceregion=CustomerAddress::where('gmpid', $sellerid)->where('status', '1')->first()->location;
        $destinationregion=CustomerAddress::where('gmpid', $user->gmpid)->where('status', '1')->first()->location;
        if ($request->logisticsprovider=="1") {
            $quantity=$itemtype=$sitem=$itemweight=$itemvalue=[];
            $carts=Cart::where('customer', $user->id)->get();
            foreach ($carts as $cart) {
                try {
                    $product=Product::where('id', $cart['product'])->first();
                    array_push($itemtype, $product->itemcat);
                    if ($product->itemcat=='2') {
                        array_push($sitem, $product->packagetype);
                    }else{
                        array_push($sitem, "1");
                    }
                    $vol_wgt = ($product->height * $product->width * $product->length) / 5000;
                    if ($vol_wgt>$product->weight) {
                        array_push($itemweight, $vol_wgt);
                    } else {
                        array_push($itemweight, $product->weight);
                    }
                    array_push($itemvalue, $product->amount);
                    array_push($quantity, $cart['quantity']);
                } catch (\Throwable $th) {
                    continue;
                }

            }
            $createrequest = Http::withHeaders([
                "content-type" => "application/json",
                // "Authorization" => "Bearer ",
            ])->get(env('SOLVENT_BASE_URL_LIVE').'/api/shipment/getquote', [
                "pickupvehicle"=>"1",
                "deliverymode"=>"1",
                "pickupcenter"=>"1",
                "sourceregion"=>$sourceregion,
                "destinationregion"=>$destinationregion,
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
                return response()->json(["message" => "An Error occurred while getting quote", "status" => "error"], 400);
            }else{
                if ($res['status']=="error") {
                    return response()->json(["message" => $res['message'], "amount"=>$res['amount'], "status" => "error"], 400);
                }else{
                    $fee=strval($res['amount']) + 1000;
                    $response=[
                        "message" => "Operation Successfully",
                        "fee" => strval($fee),
                        "homedelivery" => strval($fee),
                        "status" => "success"
                    ];
                    return response()->json($response, 200);
                }
            }
        }

        $response=[
            "message" => "Operation Successfully",
            "fee" => "2000",
            "homedelivery" => "2000",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getShippingRate2 (Request $request)
    {
        $user=auth()->user();
        $request->validate([
            'productid' => 'required',
            'quantity' => 'required',
            'region' => 'required|integer',
            'logisticsprovider' => 'required|integer',
            'deliverymode' => 'required|integer'
        ]);
        $error=$output=array();

        if(!empty($error)){
            return response()->json(["message" => $error, "status" => "error"], 400);
        }

        $product=Product::where('id', $request->productid)->first();
        $sellerid =$product->gmpid;
        $sourceregion=CustomerAddress::where('gmpid', $sellerid)->where('status', '1')->first()->location;
        $destinationregion=CustomerAddress::where('gmpid', $user->gmpid)->where('status', '1')->first()->location;
        if ($request->logisticsprovider=="1") {
            $quantity=$itemtype=$sitem=$itemweight=$itemvalue=[];

            try {
                array_push($itemtype, $product->itemcat);
                if ($product->itemcat=='2') {
                    array_push($sitem, $product->packagetype);
                }else{
                    array_push($sitem, "1");
                }
                $vol_wgt = ($product->height * $product->width * $product->length) / 5000;
                if ($vol_wgt>$product->weight) {
                    array_push($itemweight, $vol_wgt);
                } else {
                    array_push($itemweight, $product->weight);
                }
                array_push($itemvalue, $product->amount);
                array_push($quantity, $request->quantity);
            } catch (\Throwable $th) {
                return response()->json(["message" => 'Caught exception: ',  $th->getMessage(), "status" => "error"], 400);
            }

            $createrequest = Http::withHeaders([
                "content-type" => "application/json",
                // "Authorization" => "Bearer ",
            ])->get(env('SOLVENT_BASE_URL_LIVE').'/api/shipment/getquote', [
                "pickupvehicle"=>"1",
                "deliverymode"=>"1",
                "pickupcenter"=>"1",
                "sourceregion"=>$sourceregion,
                "destinationregion"=>$destinationregion,
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
                return response()->json(["message" => "An Error occurred while getting quote", "status" => "error"], 400);
            }else{
                if ($res['status']=="error") {
                    return response()->json(["message" => $res['message'], "amount"=>$res['amount'], "status" => "error"], 400);
                }else{
                    $fee=strval($res['amount']) + 1000;
                    $response=[
                        "message" => "Operation Successfully",
                        "fee" => strval($fee),
                        "homedelivery" => strval($fee),
                        "status" => "success"
                    ];
                    return response()->json($response, 200);
                }
            }
        }

        $response=[
            "message" => "Operation Successfully",
            "fee" => "2000",
            "homedelivery" => "2000",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function addToOrder(MakeOrderRequest $request)
    {
        $user=auth()->user();
        if(!$this->checkcartItemsA($user->id)){
            return response()->json(["message" => "Some Items may not be Available.", "status" => "error"], 400);
        }

        $addressbook=CustomerAddress::where('gmpid', $user->gmpid)->where('status', '1')->first();
        $phone =$addressbook->phonenumber;
        $address = $addressbook->address;
        $region = $addressbook->location;
        $items = $this->getcartItems($user->id)['items'];
        $storeid = $this->getcartItems($user->id)['storeid'];
        $sellerid =Store::where('id', $storeid)->first()->gmpid;
        $orderamount = str_replace(',', '', $request->orderamount);
        $servicefee = str_replace(',', '', $request->servicefee);
        $totalamount = $orderamount+$servicefee;
        $paymentmethod = $request->paymentmethod;
        $deliverymode = $request->deliverymode;
        $orderid='GMPO'.time();
        $order_date=time();
        $totalweight = $this->getcartItems($user->id)['totalweight'];

        $error = array();
        if(empty($items)){
          unset($request->totalamount);
          unset($request->orderamount);
          array_push($error, 'Cart is Empty');
        }
        if ($request->paymentmethod=='1') {
            if(!$this->checkWallet($totalamount)){
                return response()->json(["message" => ["Insuficient Funds"], "status" => "error"], 400);
            }
            $p_status='1';
        }else{
            $p_status='0';
        }
        if (empty($error)) {
            $order = Order::create([
                'orderid' => $orderid,
                'customer' => $user->gmpid,
                'storeid'=>$storeid,
                'sellerid'=>$sellerid,
                'products' => $items,
                'address'=>$address,
                'phone'=>$phone,
                'region'=>$region,
                'orderamount'=>$orderamount,
                'servicefee'=>$servicefee,
                'totalamount'=>$totalamount,
                'totalweight'=>$totalweight,
                'odate'=>$order_date,
                "paymentmethod" => $paymentmethod,
                "logisticsprovider" => $request->logisticsprovider,
                "deliverymode" => $deliverymode,
                'tx_ref'=>$orderid,
                'currency'=>'NGN'
            ]);
            //$this->clearCart($user->id);
            if ($request->paymentmethod=='1') {
                $this->chargeWallet($totalamount);
                date_default_timezone_set("Africa/Lagos");
                $time=date('d-m-Y h:ia');
                Order::where('id', $order->id)->update(['p_status' => '1', 'placedtime' => $time]);
                FundingHistory::create([
                    'fundingid' => $order->id,
                    'gmpid' => $order->customer,
                    'amount'=>$totalamount,
                    'ftime'=>time(),
                    'currency'=>'NGN',
                    'status'=>'1',
                    'type'=>'2',
                    'which'=>'3'
                ]);
                $this->clearCart($user->id);
                $this->NotifyMe("Order Booked Successfully", $order->orderid, "2", "3");
                $this->NotifyMe("Account Debited", "Your wallet is Charged for ".$order->orderid, "2", "1");
                $response=[
                    "message" => "Order Booked Successfully",
                    'order' => $order,
                    "status" => "success"
                ];
                return response()->json($response, 201);
            }else{
                ///Pay with payment gateway
                $useragent=$_SERVER['HTTP_USER_AGENT'];
                // $pamount=(int)$amount*100;
                $pamount=(int)$totalamount;
                $paymentrequest = Http::withHeaders([
                    "Authorization" => "Bearer ".env('FW_KEY'),
                    "content-type" => "application/json",
                    "Cache-Control" => "no-cache",
                    "User-Agent" => $useragent,
                ])->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref' => $orderid,
                    'amount' => $pamount,
                    'currency' => 'NGN',
                    'redirect_url' => ($request->payfrom=="2") ? 'http://localhost:5173/verifypaymentaddtoorder' : 'https://gavice.com/gmp-payment',
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
                    'order' => $order,
                    "status" => "success"
                ];
                return response()->json($response, 201);

            }

        }else{
            $response=[
                "message" => $error,
                "status" => "error"
            ];
            //print_r($error); exit();
            return response()->json($response, 400);
        }
    }

    public function BuyNow(BuyNowRequest $request)
    {
        $user=auth()->user();
        $addressbook=CustomerAddress::where('gmpid', $user->gmpid)->where('status', '1')->first();
        $phone =$addressbook->phonenumber;
        $address = $addressbook->address;
        $region = $addressbook->location;
        $items = $request->productid.'|'.$request->quantity;
        $product=Product::where('id', $request->productid)->first();
        $storeid = $product->storeid;
        $sellerid =$product->gmpid;
        $orderamount = str_replace(',', '', $request->orderamount);
        $servicefee = str_replace(',', '', $request->servicefee);
        $totalamount = $orderamount+$servicefee;
        $paymentmethod = $request->paymentmethod;
        $deliverymode = $request->deliverymode;
        $orderid='GMPO'.time();
        $order_date=time();
        $totalweight = $product->weight;
        if ($request->paymentmethod=='1') {
            if(!$this->checkWallet($totalamount)){
                return response()->json(["message" => ["Insuficient Funds"], "status" => "error"], 400);
            }
            $p_status='1';
        }else{
            $p_status='0';
        }


        $error = array();
        // if(empty($items)){
        //   unset($request->totalamount);
        //   unset($request->orderamount);
        //   array_push($error, 'Cart is Empty');
        // }
        if(empty($phone) || empty($address) || empty($region)){
            array_push($error, 'All fields are required');
        }
        if (empty($error)) {
            $order = Order::create([
                'orderid' => $orderid,
                'customer' => $user->gmpid,
                'storeid'=>$storeid,
                'sellerid'=>$sellerid,
                'products' => $items,
                'address'=>$address,
                'phone'=>$phone,
                'region'=>$region,
                'orderamount'=>$orderamount,
                'servicefee'=>$servicefee,
                'totalamount'=>$totalamount,
                'totalweight'=>$totalweight,
                'odate'=>$order_date,
                "paymentmethod" => $paymentmethod,
                "logisticsprovider" => $request->logisticsprovider,
                "deliverymode" => $deliverymode,
                'tx_ref'=>$orderid,
                'currency'=>'NGN'
            ]);

            if ($request->paymentmethod=='1') {
                $this->chargeWallet($totalamount);
                Order::where('id', $order->id)->update(['p_status' => '1']);
                FundingHistory::create([
                    'fundingid' => $order->id,
                    'gmpid' => $order->customer,
                    'amount'=>$totalamount,
                    'ftime'=>time(),
                    'currency'=>'NGN',
                    'status'=>'1',
                    'type'=>'2',
                    'which'=>'3'
                ]);
                $this->NotifyMe("Order Booked Successfully", $order->orderid, "2", "3");
                $this->NotifyMe("Account Debited", "Your wallet is Charged for ".$order->orderid, "2", "1");
                $response=[
                    "message" => "Order Booked Successfully",
                    'order' => $order,
                    "status" => "success"
                ];
                return response()->json($response, 201);
            }else{
                ///Pay with payment gateway
                $useragent=$_SERVER['HTTP_USER_AGENT'];
                // $pamount=(int)$amount*100;
                $pamount=(int)$totalamount;
                $paymentrequest = Http::withHeaders([
                    "Authorization" => "Bearer ".env('FW_KEY'),
                    "content-type" => "application/json",
                    "Cache-Control" => "no-cache",
                    "User-Agent" => $useragent,
                ])->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref' => $orderid,
                    'amount' => $pamount,
                    'currency' => 'NGN',
                    'redirect_url' => ($request->payfrom=="2") ? 'http://localhost:5173/verifypaymentbuynow' : 'https://gavice.com/gmp-payment',
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
                    'order' => $order,
                    "status" => "success"
                ];
                return response()->json($response, 201);

            }

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
        $tranx=$request->tx_ref;
        if (empty($tranx)) {
            return response()->json(["message"=>"Verification error. No Transaction Id given.", "status"=>"error"], 400);
        } else {
            $tx=Order::where('tx_ref', $tranx)->first();
            if (!$tx) {
                return response()->json(["message"=>"Order doesn't exist", "status"=>"error"], 400);
            }
            if ($tx->p_status=="1") {
                return response()->json(["message"=>"Transaction value already given", "status"=>"error"], 400);
            }
            $amount = $tx->totalamount;
            $userid=$tx->customer;
            $customer = Customer::where('gmpid', $userid)->first();
            $purchaseid=$tx->id;
            $currency = 'NGN';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=$tranx",
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
                Order::where('orderid', $tranx)->update([
                    'p_status' => '2'
                ]);
                return response()->json(["message"=>"cURL Error #:" . $err, "status"=>"error"], 400);
            } else {
                $resp = json_decode($response, true);
            }
            //print_r($resp); exit();
            $transaction = json_decode($response, FALSE);
            if( ($transaction->status=="success") && ($transaction->data->status=="successful")
            && ($transaction->data->amount>=$amount) && ($transaction->data->currency=="NGN") ){
                $this->clearCart($customer->id);
                date_default_timezone_set("Africa/Lagos");
                $time=date('d-m-Y h:ia');
                $sup=Order::where('id', $purchaseid)->update(['p_status' => '1', 'placedtime' => $time]);
                $sup=Order::where('id', $purchaseid)->first();
                $customer=Customer::where('gmpid', $sup->customer)->first();
                $personnelphones=[];
                $items=explode(",", $sup->products); $item = explode("|", $items[0]);
                $itemowner=$this->GetItemOwner($item[0]);
                $ownerphone=Customer::where('gmpid', $itemowner)->first()->phone;
                array_push($personnelphones, $ownerphone);
                foreach ($personnelphones as $personnelphone) {
                    $details = [
                        'phone'=>'234'.substr($personnelphone, 0),
                        'message'=>"GMP Order Placed from a customer(".$customer->name.")."
                    ];
                    try {
                        dispatch(new ConfirmAvailabilityJob($details))->delay(now()->addSeconds(1));
                    } catch (\Throwable $e) {
                        report($e);
                        Log::error('Error in sending otp: '.$e->getMessage());
                        return response()->json(["message" => "Operation Failed", "status" => "error"], 400);
                    }
                }
                $this->NotifyMe("Order Booked Successfully", $sup->orderid, "3", "3");
                return response()->json([
                    'message' => 'Payment Successful',
                    'delivery_details' => $sup,
                    'status' => 'success'
                ], 200);
            } else {
                //Dont Give Value and return to Failure page
                $sup=Order::where('id', $purchaseid)->update(['p_status' => '2']);
                return response()->json([
                    'message' => "Payment Error. Cross check payment.",
                    'status' => "error"
                ], 400);
            }
        }
    }

    public function verifypaymentbuynow(Request $request)
    {
        $tranx=$request->tx_ref;
        if (empty($tranx)) {
            return response()->json(["message"=>"Verification error. No Transaction Id given.", "status"=>"error"], 400);
        } else {
            $tx=Order::where('tx_ref', $tranx)->first();
            if (!$tx) {
                return response()->json(["message"=>"Order doesn't exist", "status"=>"error"], 400);
            }
            if ($tx->p_status=="1") {
                return response()->json(["message"=>"Transaction value already given", "status"=>"error"], 400);
            }
            $amount = $tx->totalamount;
            $userid=$tx->customer;
            $customer = Customer::where('gmpid', $userid)->first();
            $purchaseid=$tx->id;
            $currency = 'NGN';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=$tranx",
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
                Order::where('orderid', $tranx)->update([
                    'p_status' => '2'
                ]);
                return response()->json(["message"=>"cURL Error #:" . $err, "status"=>"error"], 400);
            } else {
                $resp = json_decode($response, true);
            }
            //print_r($resp); exit();
            $transaction = json_decode($response, FALSE);
            if( ($transaction->status=="success") && ($transaction->data->status=="successful")
            && ($transaction->data->amount>=$amount) && ($transaction->data->currency=="NGN") ){
                date_default_timezone_set("Africa/Lagos");
                $time=date('d-m-Y h:ia');
                $sup=Order::where('id', $purchaseid)->update(['p_status' => '1', 'placedtime' => $time]);
                $sup=Order::where('id', $purchaseid)->first();
                $customer=Customer::where('gmpid', $sup->customer)->first();
                $personnelphones=[];
                $items=explode(",", $sup->products); $item = explode("|", $items[0]);
                $itemowner=$this->GetItemOwner($item[0]);
                $ownerphone=Customer::where('gmpid', $itemowner)->first()->phone;
                array_push($personnelphones, $ownerphone);
                foreach ($personnelphones as $personnelphone) {
                    $details = [
                        'phone'=>'234'.substr($personnelphone, 0),
                        'message'=>"GMP Order Placed from a customer(".$customer->name.")."
                    ];
                    try {
                        dispatch(new ConfirmAvailabilityJob($details))->delay(now()->addSeconds(1));
                    } catch (\Throwable $e) {
                        report($e);
                        Log::error('Error in sending otp: '.$e->getMessage());
                        return response()->json(["message" => "Operation Failed", "status" => "error"], 400);
                    }
                }
                $this->NotifyMe("Order Booked Successfully", $sup->orderid, "3", "3");
                return response()->json([
                    'message' => 'Payment Successful',
                    'delivery_details' => $sup,
                    'status' => 'success'
                ], 200);
            } else {
                //Dont Give Value and return to Failure page
                $sup=Order::where('id', $purchaseid)->update(['p_status' => '2']);
                return response()->json([
                    'message' => "Payment Error. Cross check payment.",
                    'status' => "error"
                ], 400);
            }
        }
    }

    private function GetItemOwner($item){
        $item=Product::where('id', $item)->first();
        return $item->gmpid;
    }


}
