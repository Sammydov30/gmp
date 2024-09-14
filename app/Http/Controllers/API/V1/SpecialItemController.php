<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SpecialItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $getrequest = Http::withHeaders([
            "content-type" => "application/json",
            // "Authorization" => "Bearer ",
        ])->get(env('SOLVENT_BASE_URL_LIVE2').'/api/lists/specialitems', [
            "type"=>"4",
        ]);
        $res=$getrequest->json();
        $response=[
            "message" => "Fetched Successfully",
            'specialitems' => $res,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getSpecialItem(Request $request)
    {
        $getrequest = Http::withHeaders([
            "content-type" => "application/json",
        ])->get(env('SOLVENT_BASE_URL_LIVE').'/api/lists/specialitems', [
            "type"=>"5",
            'id'=> $request->id
        ]);
        $res=$getrequest->json();
        $response=[
            "message" => "Fetched Successfully",
            'specialitems' => $res,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }


}
