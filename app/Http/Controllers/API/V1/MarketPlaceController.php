<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketPlace\CreateRequest;
use App\Models\MarketPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketPlaceController extends Controller
{

    public function index()
    {
        $result = MarketPlace::with('region')->withCount('stores');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("region") != null) {
            $region=request()->input("region");
            $result->where('region', $region);
        }
        if (request()->input("location") != null) {
            $location=request()->input("location");
            $result->where('location', $location);
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

        $park=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        //$park=$result->orderBY($sortBy, $sortOrder)->get();
        return response()->json($park, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateRequest $request)
    {
        $query=MarketPlace::where('name', "like", "%{$request->name}%")->where('region', $request->region)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $marketplace = MarketPlace::create([
            'marketid' => 'GMKP'.time(),
            'name' => $request->name,
            'region'=> $request->region,
            'location'=> $request->location,
        ]);

        $response=[
            "message" => "Market Place Created Successfully",
            'marketplace' => $marketplace,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $marketplace=MarketPlace::find($id);
        if (!$marketplace) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Market Place found",
            'marketplace' => $marketplace,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CreateRequest $request, MarketPlace $marketplace)
    {
        $query=MarketPlace::where('name', "like", "%{$request->name}%")->where('region', $request->region)->
        where('id', '!=', $marketplace->id)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $marketplace->update([
            'name' => $request->name,
            'region'=> $request->region,
            'location'=> $request->location,
        ]);
        $response=[
            "message" => "Market Place Updated Successfully",
            'market' => $marketplace,
            "status" => "success"
        ];

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(MarketPlace $marketplace)
    {
        $marketplace->delete();
        $response=[
            "message" => "Market Place Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
