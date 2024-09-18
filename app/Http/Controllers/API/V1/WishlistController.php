<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuyNowRequest;
use App\Http\Requests\MakeOrderRequest;
use App\Jobs\ConfirmAvailabilityJob;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\FundingHistory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Wishlist;
use App\Traits\GMPCustomerBalanceTrait;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    use GMPCustomerBalanceTrait, NotificationTrait;

    public function index()
    {
        $user=auth()->user();
        $result = Wishlist::where('customer', $user->id);

        if (request()->input("item") != null) {
            $item=request()->input("item");
            $result->where('product', $item);
        }
        if (request()->input("priority") != null) {
            $priority=request()->input("priority");
            $result->where('priority', $priority);
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
        $wishlist=$result->orderBY($sortBy, $sortOrder)->get();

        //Convert to json then to array (To get Pure array)
        //$wishlist=json_decode(json_encode($wishlist), true);

        return response()->json(["wishlists"=>$wishlist], 200);
    }


    private function getstorename($store)
    {
        return Store::where('id', $store)->first()->name;
    }
    public function addtowishlist(Request $request)
    {
        $user=auth()->user();
        $checkitem=Product::find($request->item);
        if (!$checkitem) {
            return response()->json(["message" => "Item not available.", "status" => "error"], 400);
        }
        $query=Wishlist::where('product', $request->item)->where('customer', $user->id)->first();
        if ($query) {
            return response()->json(["message" => "Item Already Exists in Wishlist", "status" => "error"], 400);
        }
        $wishlist = Wishlist::create([
            'product' => $request->item,
            'customer' => $user->id,
            'priority' => '1',
            'confirmed' => '1',
            // 'description'=> $request->description,
        ]);
        $wishlistnum=Wishlist::where('customer', $user->id)->get();
        $twishlistnum=count($wishlistnum);
        $response=[
            "totalwishlistnum" => $twishlistnum,
            "message" => "Item Added to wishlist",
            'wishlist' => $wishlist,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function removefromwishlist(Request $request)
    {
        $user=auth()->user();
        $wishlist=Wishlist::where('id', $request->id);
        if ($wishlist->first()) {
            $wishlist->delete();
            $wishlistnum=Wishlist::where('customer', $user->id)->get();
            $twishlistnum=count($wishlistnum);
            $response=[
                "totalwishlistnum" => $twishlistnum,
                "message" => "Item removed from wishlist",
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

    public function getwishlistItemsarray(){
        $user=auth()->user();
        $wishlists=Wishlist::where('customer', $user->id)->get();
        $items=array();
        foreach ($wishlists as $wishlist) {
          $item=$wishlist['product'];
          array_push($items, $item);
        }
        $response=[
            "message" => "Successful",
            'data' => $items,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getwishlistItems($user){
        $wishlists=Wishlist::where('customer', $user)->get();
        $items=array();
        $amount=0;
        foreach ($wishlists as $wishlist) {
          //$amount=$amount+$row['amount'];
          $item=$wishlist['product'].'|1';
          array_push($items, $item);
        }
        $items=implode(",", $items);
        $data=['items'=>$items, 'amount'=>$amount];
        return $data;
    }

    function clearwishlist($user){
        $wishlist=Wishlist::where('customer', $user);
        if ($wishlist) {
            $wishlist->delete();
            return true;
        }
        return false;
    }

    public function checkout(Request $request)
    {
        $user=auth()->user();
        if(!$this->checkwishlistItemsA($user->id)){
            return response()->json(["message" => "Some items may not be available. Remove item(s) and try again", "status" => "error"], 400);
        }
        // if(!$this->checkwishlistItemsB($user->id)){
        //     return response()->json(["message" => "Please recheck the availability of the items", "status" => "error"], 400);
        // }
        return response()->json(["message" => "Operation Successful", "status" => "success"], 200);
    }

    public function getShippingRate (Request $request)
    {
        $error=$output=array();
        if (empty($request->region)) {
            array_push($error,"Region is Required");
          }
        if(!empty($error)){
            return response()->json(["message" => $error, "status" => "error"], 400);
        }
        $region=$request->region;

        $response=[
            "message" => "Operation Successfully",
            "fee" => "2000",
            "homedelivery" => "2000",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function addtocartfromwishlist(Request $request)
    {
        $request->validate([
            'wishlistid' => 'required|numeric',
        ]);
        $wishlist=Wishlist::where('id', $request->wishlistid)->first();
        $checkitem=Product::find($wishlist->product);
        if (!$checkitem) {
            return response()->json(["message" => "Item not available.", "status" => "error"], 400);
        }
        $query=Cart::where('product', $wishlist->product)->where('customer', $wishlist->customer)->first();
        if ($query) {
            $cart = Cart::where('product', $wishlist->product)->where('customer', $wishlist->product)->update([
                'quantity' => $query->quantity+1,
            ]);
        }else{
            $cart = Cart::create([
                'product' => $wishlist->product,
                'customer' => $wishlist->customer,
                'quantity' => '1',
                'availability' => '1',
                'confirmed' => '1',
                // 'description'=> $request->description,
            ]);
        }
        $cartnum=Cart::where('customer', $wishlist->customer)->get();
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

    public function addToOrder(MakeOrderRequest $request)
    {
        $user=auth()->user();
        if ($request->paymentmethod=='1') {
            if(!$this->checkWallet($request->totalamount)){
                return response()->json(["message" => "Insuficient Funds", "status" => "error"], 400);
            }
            $p_status='1';
        }else{
            $p_status='0';
        }
        $phone = $user->phone;
        $address = $request->address;
        $region = $request->region;
        $items = $this->getwishlistItems($user->id)['items'];
        $orderamount = str_replace(',', '', $request->orderamount);
        $servicefee = str_replace(',', '', $request->servicefee);
        $totalamount = $orderamount+$servicefee;
        $paymentmethod = $request->paymentmethod;
        $deliverymode = $request->deliverymode;
        $orderid='GMPO'.time();
        $order_date=time();

        $error = array();
        if(empty($items)){
          unset($request->totalamount);
          unset($request->orderamount);
          array_push($error, 'wishlist is Empty');
        }
        if(empty($phone) || empty($address) || empty($region)){
            array_push($error, 'All fields are required');
        }
        if (empty($error)) {
            $order = Order::create([
                'orderid' => $orderid,
                'customer' => $user->gmpid,
                'products' => $items,
                'address'=>$address,
                'phone'=>$phone,
                'region'=>$region,
                'orderamount'=>$orderamount,
                'servicefee'=>$servicefee,
                'totalamount'=>$totalamount,
                'odate'=>$order_date,
                "paymentmethod" => $paymentmethod,
                "deliverymode" => $deliverymode,
                'tx_ref'=>$orderid,
                'currency'=>'NGN'
            ]);
            $this->clearwishlist($user->id);

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
        if ($request->paymentmethod=='1') {
            if(!$this->checkWallet($request->totalamount)){
                return response()->json(["message" => "Insuficient Funds", "status" => "error"], 400);
            }
            $p_status='1';
        }else{
            $p_status='0';
        }
        $phone = $user->phone;
        $address = $request->address;
        $region = $request->region;
        //getwishlistitems= ['items'=>'product|quantity', 'amount'=>'0'];
        $items = $request->productid.'|'.$request->quantity;
        $orderamount = str_replace(',', '', $request->orderamount);
        $servicefee = str_replace(',', '', $request->servicefee);
        $totalamount = $orderamount+$servicefee;
        $paymentmethod = $request->paymentmethod;
        $deliverymode = $request->deliverymode;
        $orderid='GMPO'.time();
        $order_date=time();

        $error = array();
        // if(empty($items)){
        //   unset($request->totalamount);
        //   unset($request->orderamount);
        //   array_push($error, 'wishlist is Empty');
        // }
        if(empty($phone) || empty($address) || empty($region)){
            array_push($error, 'All fields are required');
        }
        if (empty($error)) {
            $order = Order::create([
                'orderid' => $orderid,
                'customer' => $user->gmpid,
                'products' => $items,
                'address'=>$address,
                'phone'=>$phone,
                'region'=>$region,
                'orderamount'=>$orderamount,
                'servicefee'=>$servicefee,
                'totalamount'=>$totalamount,
                'odate'=>$order_date,
                "paymentmethod" => $paymentmethod,
                "deliverymode" => $deliverymode,
                'tx_ref'=>$orderid,
                'currency'=>'NGN'
            ]);
            $this->clearwishlist($user->id);

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
            $customer = Customer::where('id', $userid)->first();
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
                $time=date('h:i:s a');
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
