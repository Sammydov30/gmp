<?php

namespace App\Http\Controllers\API\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePinRequest;
use App\Http\Requests\Customer\ChangePasswordRequest;
use App\Http\Requests\Customer\ChangeProfilePictureRequest;
use App\Http\Requests\Customer\ChangeProfileRequest;
use App\Http\Requests\SetPinRequest;
use App\Models\Customer;
use App\Models\Logistics;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customer=auth()->user();
        if (!empty($request->token) || $request->token=="0") {
            $customer=Customer::with('ngnbalance')->where('id', $customer->id)->update(['token'=> $request->token]);
        }
        $customer=Customer::where('id', $customer->id)->first();
        return response()->json([
            'customer' => $customer,
            "status" => "success"
        ], 200);
    }
    public function editprofile()
    {
        $customer=auth()->user();
        if (!$customer) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        return response()->json([
            'customer' => $customer,
            "status" => "success"
        ], 200);
    }

    public function setpin(SetPinRequest $request)
    {
        $user=auth()->user();
        $customer=Customer::where('id', $user->id)->update([
            'pin' => $request->pin,
        ]);
        $response=[
            "message" => "Pin Set Successfully",
            'customer' => $customer,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function updatepin(ChangePinRequest $request)
    {
        $user=auth()->user();
        $error=array();
        if ($request->cnewpin!=$request->newpin) {
            array_push($error,"New Pin Mismatch");
        }
        $user=Customer::where('id', $user->id);
        if ( $user) {
            array_push($error,"User doesn't exist");
        }
        $newpin= ($request->newpin);
        $oldpin= ($request->oldpin);
        if ($oldpin!=$user->pin) {
            array_push($error,"Old Pin is Incorrect");
        }
        if(empty($error)){
            $user->update([
                'pin' => $newpin,
            ]);
            $response=[
                "status" => "success",
                "message" => "Pin Changed Successfully",
                'user' => $user,
            ];
            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
    }

}
