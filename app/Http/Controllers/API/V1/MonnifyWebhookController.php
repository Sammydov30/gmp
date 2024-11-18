<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DepositHistory;
use App\Models\FundingHistory;
use App\Models\Haulage;
use App\Models\PaymentHistory;
use App\Models\Shipment;
use App\Traits\ActionLogTrait;
use App\Traits\GetMonnifyTokenTrait;
use App\Traits\GMPCustomerBalanceTrait;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MonnifyWebHookController extends Controller
{
    use ActionLogTrait;
    use NotificationTrait;
    use GMPCustomerBalanceTrait;
    use GetMonnifyTokenTrait;

    public function depositSolventAction(Request $request)
    {
        // Your webhook handling logic here...
        $body=json_decode($request->body, true);
        //json_decode(json_encode($data), true);
        $tx_ref=@$body["eventData"]["paymentReference"];
        $paidOn=@$body["eventData"]["paidOn"];
        $amountpaid=@$body["eventData"]["amountPaid"];
        $paymentMethod=@$body["eventData"]["paymentMethod"];
        $settlementAmount=@$body["eventData"]["settlementAmount"];
        $paymentStatus=@$body["eventData"]["paymentStatus"];
        $eventType=@$body["eventType"];
        $src_bankcode=@$body["eventData"]["paymentSourceInformation"][0]["bankCode"];
        $src_acctname=@$body["eventData"]["paymentSourceInformation"][0]["accountName"];
        $src_acctno=@$body["eventData"]["paymentSourceInformation"][0]["accountNumber"];
        $sessionID=@$body["eventData"]["paymentSourceInformation"][0]["sessionId"];

        $reference=@$body["eventData"]["product"]["reference"];
        $paytype=@$body["eventData"]["product"]["type"];
        $des_bankcode=@$body["eventData"]["destinationAccountInformation"]["bankCode"];
        $des_acctno=@$body["eventData"]["destinationAccountInformation"]["accountNumber"];

        if ($paytype=="RESERVED_ACCOUNT") {
            /////RESERVED ACCOUNT DEPOSIT
            //////////////////////////////////
            /////////////////////////////////
            if (empty($reference)) {
                return response()->json(["message"=>"Verification error. No Transaction ID given.", "status"=>"error"], 400);
            } else {
                $tx=PaymentHistory::where('reference', $tx_ref)->where('status', '1')->first();
                if ($tx) {
                    return response()->json(["message"=>"Transaction value already given", "status"=>"sucess"], 200);
                }
                $amount = $amountpaid;
                $currency = 'NGN';
                if ($paymentStatus!="PAID") {
                    $deposit = PaymentHistory::where('reference', $tx_ref)->update([
                        'status' => '2',
                    ]);
                    return response()->json([
                        'message' => "Payment returned error",
                        'status' => "error"
                    ], 400);
                }
                $deposit = PaymentHistory::where('reference', $tx_ref)->update([
                    'status' => '1',
                ]);


                if ($tx->shipmenttype!='4') {
                    Shipment::where('trackingid', $tx->shipmentid)->update([
                        'p_status' => '1',
                        'paymenttype' => '1',
                        'paymentmethod' => '2',
                        'paymentproof'=>$tx_ref,
                        'transfer_channel'=>'MONNIFY'
                    ]);
                }else{
                    Haulage::where('trackingid', $tx->shipmentid)->update([
                        'p_status' => '1',
                        'paymenttype' => '1',
                        'paymentmethod' => '2',
                        'paymentproof'=>$tx_ref,
                        'transfer_channel'=>'MONNIFY'
                    ]);
                }
                $deposit=PaymentHistory::where('reference', $tx_ref)->first();
                return response()->json([
                    'message' => 'Payment made Successfully',
                    'details' => $deposit,
                    'status' => 'success'
                ], 200);
            }
        }else{
            /////NOT RESERVED ACCOUNT DEPOSIT
            //////////////////////////////////
            /////////////////////////////////
            if (empty($reference)) {
                return response()->json(["message"=>"Verification error. No Transaction ID given.", "status"=>"error"], 400);
            } else {
                $tx=PaymentHistory::where('reference', $tx_ref)->where('status', '1')->first();
                if (!$tx) {
                    return response()->json(["message"=>"Transaction doesn't exist", "status"=>"error"], 400);
                }
                if ($tx) {
                    return response()->json(["message"=>"Transaction value already given", "status"=>"sucess"], 200);
                }
                $amount = $amountpaid;
                $currency = 'NGN';
                if ($paymentStatus!="PAID") {
                    $deposit = PaymentHistory::where('reference', $tx_ref)->update([
                        'status' => '2',
                    ]);
                    return response()->json([
                        'message' => "Payment returned error",
                        'status' => "error"
                    ], 400);
                }
                $deposit = PaymentHistory::where('reference', $tx_ref)->update([
                    'status' => '1',
                ]);


                if ($tx->shipmenttype!='4') {
                    Shipment::where('trackingid', $tx->shipmentid)->update([
                        'p_status' => '1',
                        'paymenttype' => '1',
                        'paymentmethod' => '2',
                        'paymentproof'=>$tx_ref,
                        'transfer_channel'=>'MONNIFY'
                    ]);
                }else{
                    Haulage::where('trackingid', $tx->shipmentid)->update([
                        'p_status' => '1',
                        'paymenttype' => '1',
                        'paymentmethod' => '2',
                        'paymentproof'=>$tx_ref,
                        'transfer_channel'=>'MONNIFY'
                    ]);
                }
                $deposit=PaymentHistory::where('reference', $tx_ref)->first();
                return response()->json([
                    'message' => 'Payment made Successfully',
                    'details' => $deposit,
                    'status' => 'success'
                ], 200);
            }

        }
    }

}
