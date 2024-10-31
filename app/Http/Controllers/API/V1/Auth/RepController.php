<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRepRequest;
use App\Http\Requests\RepLoginRequest;
use App\Models\CustomerRep;


class RepController extends Controller
{
    //
    public function register(RegisterRepRequest $request)
    {

        // Create user
        $rep = CustomerRep::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ], );


        $response = [
            "message" => "Registration successful.",
            'Rep' => $rep,
            "status" => "success"
        ];
        return response()->json($response, 200);

    }



    // Login
    public function login(RepLoginRequest $request)
    {

        // Attempt to authenticate the user
        $rep = CustomerRep::where('email', $request->email)->first();

        if (!$rep || !Hash::check($request->password, $rep->password)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid email or password'], 400);
        }

        // Generate a token for the user
        $token = $rep->createToken('repToken')->plainTextToken;

        // Return success response with token

        $response = [
            'message' => 'Login successful',
            'RepID' => $rep->id,
            'token' => $token,
            'role' => 'Rep',
            'status' => 'success',
        ];
        return response()->json($response, 200);


    }

}
