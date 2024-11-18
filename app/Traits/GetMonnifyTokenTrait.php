<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait GetMonnifyTokenTrait {

    public function getMonnifyToken() {
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Basic ".env('MONNIFY_ACCESS_TOKEN'),
        ])->post('https://api.monnify.com/api/v1/auth/login');
        $res=$paymentrequest->json();
        if (!$res['requestSuccessful']) {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }
        return $res['responseBody']['accessToken'];
    }

}
