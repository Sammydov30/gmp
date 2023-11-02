<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SubscriptionController extends Controller
{
    public function fetchsubscriptions(Request $request)
    {
        $customer=auth()->user();
        $result = Subscription::where('customer', $customer->id);
        if (empty($request->plan)) {
            $result->where('plan', $request->plan);
        }
        if (request()->input("used")!=null) {
            $result->where('used', request()->input("used"));
        }
        if (!empty($request->sortBy) && in_array($request->sortBy, ['id', 'created_at'])) {
            $sortBy=$request->sortBy;
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

    public function addsubscription(Request $request)
    {
        $user=auth()->user();
        if (empty($request->plan)) {
            return response()->json(["message" => "No Plan Specified", "status" => "error"], 400);
        }
        $check=Plan::where("id", $request->plan)->first();
        if (!$check) {
            return response()->json(["message" => "Selected Plan Doesn't Exist", "status" => "error"], 400);
        }
        $check=Subscription::where("customer", $user->id)->first();
        if ($check) {
            if ($check->used=='0' && $check->ra>0) {
                return response()->json(["message" => "You have a active subscription", "status" => "error"], 400);
            }
        }
        $plan=Plan::where('id', $request->plan)->first();
        $time=time();
        if ($plan->type=='1') {
            $subscription=Subscription::updateOrCreate(
            ['customer' => $user->id],
            [
                'plan' => $plan->id,
                'currtime' => $time,
                'expiredtime' => $time+$plan->duration,
                'ra'=> $plan->hwa,
                'checkups'=> $plan->checkups,
                'used' => '0',
            ]);
            CustomerTransaction::create([
                'patientid' => $user->patientid,
                'amount' => $plan->amount,
                'tx_ref' => 'CT'.$time,
                'plan' => $plan->id,
                'ttime' => $time,
                'status' => '1',
            ]);

            return response()->json([
                "message"=>"Subscription made Successfully",
                "state"=>"1",
                "status" => "success",
                'subscription' => $subscription,
            ], 200);
        }else{
            $trnx='CT'.$time;
            $paymentrequest = Http::withHeaders([
                "Authorization" => "Bearer ".env('PAYSTACK_KEY'),
                "content-type" => "application/json",
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => $user->email,
                'amount' => $plan->amount*100,
                'currency'=>'NGN',
                'reference'=>$trnx,
                'callback_url'=>'https://call-a-doctor-api.herokuapp.com/api/v1/paystack-payment',
                // 'callback_url'=>'https://call-a-doctor-api.herokuapp.com/api/v1/paystack-payment?reference='.$trnx,
            ]);
            $payy=$paymentrequest->json();
            //print_r($payy); exit();
            if (!$payy['status']) {
                return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
            }else{
                CustomerTransaction::create([
                    'patientid' => $user->patientid,
                    'amount' => $plan->amount,
                    'tx_ref' => $trnx,
                    'access_code' => $payy['data']['access_code'],
                    'planid' => $plan->id,
                    'ttime' => $time,
                    'status' => '0',
                ]);
                return response()->json([
                    "message"=>"Subscription Initiated Successfully",
                    "state"=>"2",
                    "paymentdetails"=>$payy['data'],
                    "status" => "success",
                ], 201);
            }
        }
    }

    public function verifypayment(Request $request)
    {
        $tranx=$request->reference;
        if (empty($tranx)) {
            return response()->json(["message"=>"Verification error. No Transaction Id given.", "status"=>"error"], 400);
        } else {
            $tx=CustomerTransaction::where('tx_ref', $tranx)->first();
            if (!$tx) {
                return response()->json(["message"=>"Transaction doesn't exist", "status"=>"error"], 400);
            }
            if ($tx->status=="1") {
                return response()->json(["message"=>"Transaction value already given", "status"=>"error"], 400);
            }
            $amount = $tx->amount;
            $patientid=$tx->patientid;
            $purchaseid=$tx->id;
            $currency = 'NGN';
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.paystack.co/transaction/verify/$tranx",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer ".env('PAYSTACK_KEY'),
                "Cache-Control: no-cache",
              ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
              return response()->json(["message"=>"cURL Error #:" . $err, "status"=>"error"], 400);
            } else {
                $resp = json_decode($response, true);
            }
            //print_r($resp); exit();
            if(!empty($resp['status']) &&  $resp['status']){
                $paymentStatus = $resp['data']['status'];
                $chargeAmount = $resp['data']['amount'];
                $chargeCurrency = $resp['data']['currency'];
                $charge_tx_ref = $resp['data']['reference'];
                if (
                    ($paymentStatus == "success")
                    && ($chargeAmount >= $amount)
                    && ($chargeCurrency == $currency)
                ) {
                    //date_default_timezone_set("Africa/Lagos");
                    $time=time();
                    $transaction=CustomerTransaction::where('id', $purchaseid)->update(['status' => '1']);
                    $transaction=CustomerTransaction::where('id', $purchaseid)->first();
                    $customer = Customer::where('patientid', $patientid)->first();
                    $plan=Plan::where('id', $tx->planid)->first();
                    Subscription::updateOrCreate(
                        ['customer' => $customer->id],
                        [
                            'plan' => $plan->id,
                            'currtime' => $time,
                            'expiredtime' => $time+$plan->duration,
                            'ra'=> $plan->hwa,
                            'checkups'=> $plan->checkups,
                            'used' => '0',
                        ]);
                    return response()->json([
                        'message' => 'Payment Successful',
                        'transaction' => $transaction,
                        'status' => 'success'
                    ], 200);
                } else {
                    //Dont Give Value and return to Failure page
                    $transaction=CustomerTransaction::where('id', $purchaseid)->update(['status' => '2']);
                    return response()->json([
                        'message' => "Payment Error. Cross check payment.",
                        'status' => "error"
                    ], 400);
                }
            }else{
                $transaction=CustomerTransaction::where('id', $purchaseid)->update(['status' => '2']);
                return response()->json(["message"=>"Paystack: ".$resp['message'], "status"=>"error"], 400);
            }
        }
    }

    public function usedsubscription($sup)
    {
        $subscription=Subscription::where('id', $sup)->update([
            'used'=> '1',
        ]);
        if($subscription){
            return true;
        }
        return false;
    }

}
