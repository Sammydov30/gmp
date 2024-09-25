<?php

namespace App\Http\Controllers\API\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\CustomerImageRequest;
use App\Http\Requests\CustomerProfileRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $result = Customer::with('addressbook')->where('deleted', '0');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('firstname', "like", "%{$search}%")
            ->orWhere('lastname', "like", "%{$search}%")
            ->orWhere('othername', "like", "%{$search}%")
            ->orWhere('phone', "like", "%{$search}%")
            ->orWhere('email', "like", "%{$search}%");
        }
        if (request()->input("gmpid") != null) {
            $search=request()->input("gmpid");
            $result->where('gmpid', "like", "%{$search}%");
        }
        if ((request()->input("sortby")!=null) && in_array(request()->input("sortby"), ['id', 'name', 'created_at'])) {
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
            $perPage=10;
        }

        $customers=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        //$park=$result->orderBY($sortBy, $sortOrder)->get();
        return response()->json($customers, 200);
    }
    public function getbalance(Request $request)
    {
        $customer=Customer::where('id', $request->gmpid)->first();
        $balance=$customer->ngnbalance;
        return response()->json([
            'ngnbalance' => $balance,
            "status" => "success"
        ], 200);
    }

    public function getRegioname($region)
    {
        $regionname=Region::where('id', $region)->first()->name;
        return $regionname;
    }

    public function editprofile(Request $request)
    {
        $customer=Customer::where('id', $request->id)->first();
        if (!$customer) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        return response()->json([
            'customer' => $customer,
            "status" => "success"
        ], 200);
    }

    public function updateprofile(Request $request)
    {
        $customer=Customer::where('id', $request->id)->first();
        Customer::where('id', $request->id)->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            //'email' => $request->email,
            //'phone' => $request->phone,
        ]);
        $customer=Customer::where('id', $request->id)->first();
        $response=[
            "message" => "Profile Updated Successfully",
            'customer' => $customer,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function updatepassword(Request $request)
    {
        $customer=Customer::where('id', $request->id)->first();
        Customer::where('id', $request->id)->update([
            'password' => Hash::make($request->password),
        ]);
        $customer=Customer::where('id', $request->id)->first();
        $response=[
            "message" => "Password Changed Successfully",
            'customer' => $customer,
            "status" => "success"
        ];
        return response()->json($response, 201);
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
