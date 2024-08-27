<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivationValue;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\FundingHistory;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Traits\GMPCustomerBalanceTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SubscriptionController extends Controller
{
    use GMPCustomerBalanceTrait;

    public function fetchsubscriptions(Request $request)
    {
        $customer=auth()->user();
        $result = Subscription::where('gmpid', $customer->id);
        if (empty($request->plan)) {
            $result->where('plan', $request->plan);
        }
        if (request()->input("used")!=null) {
            $result->where('used', request()->input("used"));
        }
        if (!empty($request->sortby) && in_array($request->sortby, ['id', 'created_at'])) {
            $sortBy=$request->sortby;
        }else{
            $sortBy='id';
        }
        if (!empty($request->sortorder) && in_array($request->sortorder, ['asc', 'desc'])) {
            $sortOrder=$request->sortorder;
        }else{
            $sortOrder='desc';
        }
        if (!empty($request->perpage)) {
            $perPage=$request->perpage;
        } else {
            $perPage=10;
        }
        $supscription=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($supscription, 200);
    }

    public function checkseller(Request $request)
    {
        $user=auth()->user();
        $seller=Customer::where("gmpid", $user->gmpid)->first();
        $activationvalue=ActivationValue::where('id', '1')->first();
        $supamount=$activationvalue->subscriptionamount;
        if ($seller->seller=='0') {
            return response()->json(["message" => "No Active Subscription", "type" => "1", "amount"=> $supamount, "status" => "error"], 400);
        }
        $sup=Subscription::where("gmpid", $user->gmpid)->latest()->first();
        $currtime=time();
        if ($sup) {
            if ($currtime>$sup->expiredtime) {
                Subscription::where('id', $sup->id)->update(["used"=>"1"]);
                return response()->json(["message" => "No Active Subscription. Subscription Expired", "type" => "2", "amount"=> $supamount, "status" => "error"], 400);
            }
        }else{
            return response()->json(["message" => "No Active Subscription.", "type" => "1",   "amount"=> $supamount,"status" => "error"], 400);
        }
        $storecount=Store::where('gmpid', $user->gmpid)->where('deleted', '0')->count();
        return response()->json([
            "message"=>"Subscription Active",
            "storecount" => $storecount,
            "status" => "success"
        ], 200);
    }

    public function sellongmp(Request $request)
    {
        $user=auth()->user();
        $check=Subscription::where("gmpid", $user->gmpid)->latest()->first();
        $currtime=time();
        $storecount=Store::where('gmpid', $user->gmpid)->where('deleted', '0')->count();
        if ($check) {
            if ($currtime<$check->expiredtime) {
                return response()->json(["message" => "You have a active subscription", "storecount" => $storecount, "status" => "error"], 400);
            }
        }
        $activationvalue=ActivationValue::where('id', '1')->first();
        $supamount=$activationvalue->subscriptionamount;
        if(!$this->checkWallet($supamount)){
            return response()->json(["message" => "Insuficient Funds", "storecount" => $storecount, "status" => "error"], 400);
        }
        $sellerid='GMPS'.time();
        Customer::where('gmpid', $user->gmpid)->update(["seller"=>"1", "sellerid"=>$sellerid]);
        $time=time();
        $subscription=Subscription::create([
            'gmpid' => $user->gmpid,
            'plan' => '1',
            'currtime' => $time,
            'expiredtime' => $time+$activationvalue->subscriptionduration,
            'used' => '0',
        ]);
        $this->chargeWallet($supamount);
        FundingHistory::create([
            'fundingid' => $subscription->id,
            'gmpid' => $subscription->gmpid,
            'amount'=>$supamount,
            'ftime'=>time(),
            'currency'=>'NGN',
            'status'=>'1',
            'type'=>'2',
            'which'=>'2'
        ]);
        return response()->json([
            "message"=>"Subscription made Successfully",
            "state"=>"1",
            "storecount" => $storecount,
            "status" => "success",
            'subscription' => $subscription,
        ], 200);
    }

    public function addsubscription(Request $request)
    {
        $user=auth()->user();
        $check=Subscription::where("gmpid", $user->gmpid)->latest()->first();
        $storecount=Store::where('gmpid', $user->gmpid)->where('deleted', '0')->count();
        $currtime=time();
        if ($check) {
            if ($currtime<$check->expiredtime) {
                return response()->json(["message" => "You have a active subscription", "storecount" => $storecount, "status" => "error"], 400);
            }
        }
        $activationvalue=ActivationValue::where('id', '1')->first();
        $supamount=$activationvalue->subscriptionamount;
        if(!$this->checkWallet($supamount)){
            return response()->json(["message" => "Insuficient Funds", "storecount" => $storecount, "status" => "error"], 400);
        }
        Customer::where('gmpid', $user->gmpid)->update(["seller"=>"1"]);
        $time=time();
        $subscription=Subscription::create([
            'gmpid' => $user->gmpid,
            'plan' => '1',
            'currtime' => $time,
            'expiredtime' => $time+$activationvalue->subscriptionduration,
            'used' => '0',
        ]);
        $this->chargeWallet($supamount);
        FundingHistory::create([
            'fundingid' => $subscription->id,
            'gmpid' => $subscription->gmpid,
            'amount'=>$supamount,
            'ftime'=>time(),
            'currency'=>'NGN',
            'status'=>'1',
            'type'=>'2',
            'which'=>'2'
        ]);
        return response()->json([
            "message"=>"Subscription made Successfully",
            "state"=>"1",
            "storecount" => $storecount,
            "status" => "success",
            'subscription' => $subscription,
        ], 200);
    }

}
