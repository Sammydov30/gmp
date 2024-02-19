<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PickupCenter\CreateRequest;
use App\Models\PickupCenter;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PickupCentersController extends Controller
{

    public function index()
    {
        $result =PickupCenter::with('region')->where('deleted', '0')->where('status', '1');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("region") != null) {
            $region=request()->input("region");
            $result->where('state', $region);
        }
        if ((request()->input("sortby")!=null) && in_array(request()->input("sortby"), ['id', 'created_at'])) {
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

        $data=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($data, 200);
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

    public function store(CreateRequest $request)
    {
        $query=PickupCenter::where('name', "like", "%{$request->name}%")->where('state', $request->region)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $pickupcenter = PickupCenter::create([
            'name' => $request->name,
            'state' => $request->region,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => '1'
        ]);

        $response=[
            "message" => "Pickup Center Created Successfully",
            'pickupcenter' => $pickupcenter,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $pickupcenter=PickupCenter::with('region')->find($id);
        if (!$pickupcenter) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Pickup center found",
            'pickupcenter' => $pickupcenter,
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
    public function update(CreateRequest $request, PickupCenter $pickupcenter)
    {
        $query=PickupCenter::where('name', "like", "%{$request->name}%")->where('state', $request->region)->
        where('id', '!=', $pickupcenter->id)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $pickupcenter->update([
            'name' => $request->name,
            'state' => $request->region,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);
        $response=[
            "message" => "Pickup Center Updated Successfully",
            'pickupcenter' => $pickupcenter,
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
    public function destroy(PickupCenter $pickupcenter)
    {
        $pickupcenter->delete();
        $response=[
            "message" => "Pickup Center Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
