<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Haulage\CreateRequest;
use App\Jobs\ConfirmAvailabilityJob;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\Haulage;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HaulageController extends Controller
{
    use NotificationTrait;

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

    public function index(Request $request)
    {
        $user=auth()->user();
        $result = Haulage::where('gmpid', $user->gmpid);
        // if (!empty($request->gmpid)) {
        //     $result->where('gmpid', $request->gmpid);
        // }
        if (!empty($request->name)) {
            $result->where('name', "like", "%{$request->name}%");
        }
        if (!empty($request->phone)) {
            $result->where('phone', "like", "%{$request->phone}%");
        }
        // if (!empty($request->status)) {
        //     $result->where('status', $request->status);
        // }
        if ($request->status != null) {
            $status=request()->input("status");
            switch($status) {
              case '1':
                $result->where('status', '1');
                break;
              case '2':
                $result->where('status', '2');
                break;
              case '3':
                $result->where('status', '3');
                break;
              case '4':
                $result->where('cancel', '1');
                break;
            }
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
           $perPage=30;
        }

        $haulages=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($haulages, 200);
    }

    public function store(CreateRequest $request)
    {
        $user=auth()->user();
        $haulage = Haulage::create([
            "entity_guid"=>Str::uuid(),
            'orderid'=> $this->getDeliveryNO(),
            'trackingid'=> $this->getTrackingNO(),
            'gmpid' => $user->gmpid,
            'name' => $request->name,
            'phone'=> $request->phone,
            'inspectiondate'=>$request->inspectiondate,
            'region'=> $request->source_region,
            'destination'=> $request->destination_region,
            'address'=> $request->address,
            'description'=> $request->description,
            'solventapproved'=>'0',
            'fromgmp'=>'1',
            'user_guid'=>$request->gmpid,
            'who'=>'10',
            'rdate'=> time(),
        ]);
        //$this->notifyrider('2348067108399');
        //$this->notifyWhatsapp('2348166273168');
        $this->NotifyMe("Haulage Created Successfully", "Your Order ID is $haulage->orderid", "3", "2");
        $response=[
            "message" => "Haulage Created Successfully. Our team will reach out to you soon.",
            'haulage' => $haulage,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function show($haulage)
    {
        $haulage=Haulage::find($haulage);
        if (!$haulage) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        $response=[
            "message" => "Haulage found",
            'haulage' => $haulage,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function completed(Request $request,  Haulage $haulage)
    {
        $haulage->update([
            'status'=> $request->status,
        ]);
        $response=[
            "message" => "Haulage Completed Successfully",
            "haulage" => $haulage,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Haulage $haulage)
    {
        $haulage->delete();
        $response=[
            "message" => "Haulage Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function notifyrider($phone)
    {
        $details = [
            'phone'=>$phone,
            'message'=>"A Haulage Request has been made. Powered by Gavice"
        ];
        try {
            dispatch(new ConfirmAvailabilityJob($details))->delay(now()->addSeconds(1));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending otp: '.$e->getMessage());
            return false;
        }
        return true;
    }

    public function notifyWhatsapp($phone)
    {
        $details = [
            'phone'=>$phone,
            'message'=>"A Haulage Request has been made. Powered by Gavice"
        ];
        try {
            dispatch(new SendWhatsappMessageJob($details))->delay(now()->addSeconds(1));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending otp: '.$e->getMessage());
            return false;
        }
        return true;
    }
}
