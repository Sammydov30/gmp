<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\RegisterRequest;
use App\Http\Requests\CheckOtpRequest;
use App\Jobs\Admin\EmailOtpJob;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $admin = Admin::where('email', $request->email)->first();
        if (!$admin) {
            return response()->json(["message" => "The provided credentials are incorrect", "status" => "error"], 400);
        }
        if ($admin->status=='0') {
            return response()->json(["message" => "This Admin account is Inactive.", "status" => "error"], 400);
        }
        $otp=$this->generate_otp();
        $expiration = time()+600;
        $user = Admin::where('email', $request->email)->update(
            ['otp'=>$otp, 'expiration'=>$expiration],
        );
        $details = [
            'email'=>$request->email,
            'otp'=>$otp,
            'subject' => 'Call a Doc Account Verification',
        ];
        try {
            dispatch(new EmailOtpJob($details))->delay(now()->addSeconds(1));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending otp: '.$e->getMessage());
        }
        $response=[
            'email' => $request->email,
            "expiration" => $expiration,
            'message' => 'OTP is successfully sent to you',
            "status" => "success"
        ];
        return response()->json($response, 201);

    }

    public function check_otp(CheckOtpRequest $request)
    {
        // $otp_response=$this->confirm_otp($request->phone, $request->otp);
        $admin=Admin::where('email', $request->email)->first();
        $currtime=time();
        if($admin->otp==$request->otp){
            $user = Admin::where('email', $request->email)->update(
                ['lastlogin'=>time()],
            );
            $response=[
                'token' => $admin->createToken('calladoctor', ['role:admin'])->plainTextToken,
                "status" => "success",
                'admin' => $admin,
            ];
            return response()->json($response, 201);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }else{
            return response()->json(["message" => "Otp Verification Failed. Try again", "status" => "error"], 400);
        }

    }

    public function generate_otp(){
        $data=mt_rand(100000,999999);
        return $data;
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["message" => "Logout successful", "status" => "success"], 200);
        // auth()->user()->tokens()->delete();
        // return response()->json(['message'=>'Logout Successful']);
    }
}
