<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BankController extends Controller
{
    public function fetchbanks()
    {
        $banks=Bank::all();
        return response()->json($banks, 200);
    }
    public function explodebanks()
    {
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
        ])->get('https://api.paystack.co/bank?country=nigeria');
        $payy=$paymentrequest->json();

        $allbanks=$payy['data'];
        foreach ($allbanks as $bank) {
            Bank::create([
                'bankid' => $bank['id'],
                'bankcode' => $bank['code'],
                'name' => $bank['name'],
            ]);
        }
        die("All done");
    }

}
