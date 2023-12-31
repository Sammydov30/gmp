<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckOtpRequest;
use App\Http\Requests\Customer\EmailForgotPasswordRequest;
use App\Http\Requests\Customer\GetStartedRequest;
use App\Http\Requests\Customer\LoginRequest;
use App\Http\Requests\Customer\RegisterRequest;
use App\Http\Requests\Customer\ResetPasswordRequest;
use App\Jobs\Customer\ForgotPasswordEmailJob;
use App\Jobs\Customer\GetStartedOtpJob;
use App\Jobs\Customer\RegisterEmailJob;
use App\Jobs\CustomerForgotPasswordEmailJob;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CustomerAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $customer = Customer::where('email', $request->email)->first();
        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json(["message" => "The provided credentials are incorrect", "status" => "error"], 400);
        }
        return response()->json([
            'customer' => $customer,
            'token' => $customer->createToken('GMP', ['role:customer'])->plainTextToken,
            "status" => "success",
            "message" => "Login successful."
        ], 200);

    }

    public function getstarted(GetStartedRequest $request)
    {
        $user= Customer::where('email', $request->email)->where('status', '1')->first();
        if ($user) {
            return response()->json(["message" => "This email has already been used", "status" => "error"], 400);
        }
        $otp=$this->generate_otp();
        $expiration = time()+600;
        Customer::updateOrCreate(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'otpexpiration' => $expiration
            ],
        );
        $user = Customer::where('email', $request->email)->first();
        $subject = 'GMP Signup Verification';
        $details = [
            'otp'=> $otp,
            'email'=>$request->email,
            'subject'=>$subject
        ];
        try {
            dispatch(new GetStartedOtpJob($details))->delay(now()->addSeconds(5));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending email: '.$e->getMessage());
        }
        $response=[
            "email" => $user->email,
            "expiration" => $expiration,
            "message" => "A verification code is sent to your email. Please verify email to continue",
            "status" => "success"
        ];
        return response()->json($response, 201);
    }
    public function checkotp(CheckOtpRequest $request)
    {
        $user=Customer::where('email', $request->email)->first();
        $currtime=time();
        if($currtime>$user->otpexpiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }
        if($user->otp==$request->otp){
            $response=[
                "message" => "Email successfully Verified.",
                'email' => $user->email,
                "status" => "success"
            ];
            return response()->json($response, 200);
        }else{
            return response()->json(["message" => "Email Verification Failed. Try again", "status" => "error"], 400);
        }
    }

    public function register(RegisterRequest $request)
    {
        $query=Customer::where('email', $request->email)->where('status', '1')->first();
        if ($query) {
            return response()->json(["message" => 'Email address has already been used.', "status" => "error"], 400);
        }
        $query=Customer::where('phone', $request->phone)->where('status', '1')->first();
        if ($query) {
            return response()->json(["message" => 'Phone Number has already been used.', "status" => "error"], 400);
        }
        $customer = Customer::where('email', $request->email)->update([
            'gmpid'=>'GMP'.time(),
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'phone'=>$request->phone,
            'password' => Hash::make($request->password),
            'state' => $request->location,
            'refcode' => $this->getReferralNO(),
            'status' => '1'
        ]);
        $customer = Customer::where('email', $request->email)->first();
        $message="Welcome ".$request->name;
        $subject = 'GMP Customer Registration';
        $details = [
            'name'=>$request->firstname,
            'email'=>$request->email,
            'subject'=>$subject
        ];
        try {
            dispatch(new RegisterEmailJob($details))->delay(now()->addSeconds(20));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending email: '.$e->getMessage());
        }

        $token = $customer->createToken('GMP', ['role:customer'])->plainTextToken;
        $response=[
            'token' => $token,
            'customer' => $customer,
            "status" => "success",
            "message" => "GMP Account Created successfully."
        ];
        return response()->json($response, 201);
    }

    public function forgotpasswordwithemail(EmailForgotPasswordRequest $request)
    {
        $customer = Customer::where('email', $request->email)->first();
        if (!$customer) {
            return response()->json(["message" => "Email doesn't exist in our record", "status" => "error"], 400);
        }
        $otp=$this->generate_otp();
        $expiration = time()+600;
        $password=$this->generateRandomString(10);
        $customer = Customer::where('email', $request->email)->update([
            'otp' => $otp,
            'otpexpiration' => $expiration
        ]);
        $customer = Customer::where('email', $request->email)->first();
        $subject = 'GMP | Password Recovery';
        $title = 'Password Recovery Successful';
        $details = [
            'otp'=> $otp,
            'email'=>$request->email,
            'subject'=>$subject
        ];
        try {
            dispatch(new ForgotPasswordEmailJob($details))->delay(now()->addSeconds(5));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending email: '.$e->getMessage());
        }
        $response=[
            "email" => $customer->email,
            "expiration" => $expiration,
            "message" => "A verification code is sent to your email. Please verify email to reset password",
            "status" => "success"
        ];

        return response()->json($response, 200);
    }

    public function resetpassword(ResetPasswordRequest $request)
    {
        $query=Customer::where('email', $request->email)->where('status', '1')->first();
        $customer = Customer::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);
        $customer = Customer::where('email', $request->email)->first();

        $token = $customer->createToken('GMP', ['role:customer'])->plainTextToken;
        $response=[
            'token' => $token,
            'customer' => $customer,
            "status" => "success",
            "message" => "Account password has been reset successfully."
        ];
        return response()->json($response, 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["message" => "Logout successful", "status" => "success"], 200);
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

    public function generate_otp(){
        $data=mt_rand(100000,999999);
        return $data;
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
