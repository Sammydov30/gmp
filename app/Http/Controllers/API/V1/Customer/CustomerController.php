<?php

namespace App\Http\Controllers\API\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddAddressRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangePinRequest;
use App\Http\Requests\CustomerImageRequest;
use App\Http\Requests\CustomerProfileRequest;
use App\Http\Requests\SetPinRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customer=auth()->user();
        if (!empty($request->token) || $request->token=="0") {
            Customer::where('id', $customer->id)->update(['token'=> $request->token]);
        }
        if ($customer->refcode==null) {
            $refcode=$this->getReferralNO();
            Customer::where('id', $customer->id)->update(['refcode'=> $refcode]);
        }
        $customer=Customer::where('id', $customer->id)->first();
        return response()->json([
            'customer' => $customer,
            "status" => "success"
        ], 200);
    }

    public function checkpin(Request $request)
    {
        $user=auth()->user();
        $check=Customer::where('gmpid', $user->gmpid)->first();
        if ($check) {
            if ($check->pin==null) {
                return response()->json(["message" => "PIN Not Set", "status" => "error"], 400);
            }else{
                return response()->json(["message" => "Pin Set", "status" => "success"], 200);
            }
        }else{
            return response()->json(["message" => "An Error Occured", "status" => "error"], 400);
        }
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
        // if ($request->cnewpin!=$request->newpin) {
        //     array_push($error,"New Pin Mismatch");
        // }

        $customer=Customer::where('id', $user->id)->first();
        if (!$customer) {
            array_push($error,"User doesn't exist");
        }
        $newpin= ($request->newpin);
        $oldpin= ($request->oldpin);
        if ($oldpin!=$customer->pin) {
            array_push($error,"Old Pin is Incorrect");
        }
        if(empty($error)){
            Customer::where('id', $user->id)->update([
                'pin' => $newpin,
            ]);
            $user=Customer::where('id', $user->id)->first();
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

    public function updateprofile(CustomerProfileRequest $request)
    {
        $user=auth()->user();
        $customer=Customer::where('id', $user->id)->first();
        Customer::where('id', $user->id)->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);
        $customer=Customer::where('id', $user->id)->first();
        $response=[
            "message" => "Profile Updated Successfully",
            'customer' => $customer,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function updateprofilepicture(CustomerImageRequest $request)
    {
        $user=auth()->user();

        if ($request->file('profilepicture')) {
            $file =$request->file('profilepicture');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/profilepicture/'), $filename);
            $profilepicture= 'uploads/profilepicture/'.$filename;
        }else{
            $profilepicture=null;
        }

        $customer=Customer::where('id', $user->id)->first();
        Customer::where('id', $user->id)->update([
            'profilepicture' => $profilepicture,
        ]);
        $customer=Customer::where('id', $user->id)->select('profilepicture')->first();
        //$profilepicture=$customer->profilepicture;
        $response=[
            "message" => "Profile Picture Changed Successfully",
            'customer' => $customer,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function addaddress(AddAddressRequest $request)
    {
        $user=auth()->user();
        if ($request->setdefaultaddress=='1') {
            CustomerAddress::where('gmpid', $user->gmpid)->update(['status'=>'0']);
        }
        $address=CustomerAddress::create([
            'gmpid' => $user->gmpid,
            'location' => $request->location,
            'address' => $request->address,
            'city' => $request->city,
            'status'=>$request->setdefaultaddress
        ]);
        $response=[
            "message" => "Address Created Successfully",
            'customer' => $address,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function editaddress(AddAddressRequest $request)
    {
        $user=auth()->user();
        if ($request->setdefaultaddress=='1') {
            CustomerAddress::where('gmpid', $user->gmpid)->update(['status'=>'0']);
        }
        $address=CustomerAddress::where('id', $request->addressid)->update([
            'gmpid' => $user->gmpid,
            'location' => $request->location,
            'address' => $request->address,
            'city' => $request->city,
            'status'=>$request->setdefaultaddress
        ]);
        $response=[
            "message" => "Address saved Successfully",
            'customer' => $address,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function deleteaddress(Request $request)
    {
        $user=auth()->user();
        $address = CustomerAddress::find($request->id);
        $address->delete();

        CustomerAddress::where('gmpid', $user->gmpid)->latest()->update(['status'=>'1']);

        $response=[
            "message" => "Address Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function listaddress(Request $request)
    {
        $customer=auth()->user();
        $addresses=CustomerAddress::where('gmpid', $customer->gmpid)->get();
        return response()->json([
            'addresses' => $addresses,
            "status" => "success"
        ], 200);
    }

    public function updatepassword(ChangePasswordRequest $request)
    {
        $user=auth()->user();
        $customer=Customer::where('id', $user->id)->first();
        if (!$customer || !Hash::check($request->oldpassword, $customer->password)) {
            return response()->json(["message" => "Old password is not correct", "status" => "error"], 400);
        }
        Customer::where('id', $user->id)->update([
            'password' => Hash::make($request->password),
        ]);
        $customer=Customer::where('id', $user->id)->first();
        $response=[
            "message" => "Password Changed Successfully",
            'customer' => $customer,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function getReferralNO() {
        $i=$k=0;
        while ( $i==0) {
          $refcode=rand(100000, 999999);
          $refcode=strtoupper($this->generateRandomString(5).$refcode);
          $checkref=Customer::where('refcode', $refcode)->count();
          $k++;
          if ($k==20) {
            return strtoupper($this->generateRandomString(3).time());
          }
          if ($checkref<1) {
            $i=1;
          }
        }
        return strtoupper($refcode);
    }

    public function generateRandomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}
