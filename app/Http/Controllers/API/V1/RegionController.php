<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $createrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL').'/api/lists/regions', [
            "type"=>"4",
        ]);
        $res=$createrequest->json();
        $response=[
            "message" => "Fetched Successfully",
            'regions' => $res,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getRegion(Request $request)
    {
        $createrequest = Http::withHeaders([
            "content-type" => "application/json",
        ])->get(env('SOLVENT_BASE_URL').'/api/lists/regions', [
            "type"=>"5",
        ]);
        $res=$createrequest->json();
        $response=[
            "message" => "Fetched Successfully",
            'regions' => $res,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }


}
