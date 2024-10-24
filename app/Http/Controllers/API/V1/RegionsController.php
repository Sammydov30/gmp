<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Region\CreateRequest;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegionsController extends Controller
{

    public function index()
    {
        $result = Region::with('country', 'mstate')->where('deleted', '0')->where('status', '1');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("country") != null) {
            $country=request()->input("country");
            $result->where('country', $country);
        }
        if ((request()->input("sortby")!=null) && in_array(request()->input("sortby"), ['id', 'name', 'created_at'])) {
            $sortBy=request()->input("sortby");
        }else{
            $sortBy='name';
        }
        if ((request()->input("sortorder")!=null) && in_array(request()->input("sortorder"), ['asc', 'desc'])) {
            $sortOrder=request()->input("sortorder");
        }else{
            $sortOrder='asc';
        }
        if (!empty(request()->input("perpage"))) {
            $perPage=request()->input("perpage");
        } else {
            $perPage=100;
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
        // $query=Region::where('name', "like", "%{$request->name}%")->where('country', $request->country)->first();
        // if ($query) {
        //     return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        // }
        $region = Region::create([
            'entity_guid'=>Str::uuid(),
            'name' => $request->name,
            'country' => $request->country,
            'state' => $request->state,
            'status' => '1'
        ]);

        $response=[
            "message" => "Region Created Successfully",
            'region' => $region,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $region=Region::with('country')->find($id);
        if (!$region) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Region found",
            'region' => $region,
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
    public function update(CreateRequest $request, $id)
    {
        // $query=Region::where('name', "like", "%{$request->name}%")->where('country', $request->country)->
        // where('id', '!=', $region->id)->first();
        // if ($query) {
        //     return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        // }
        $region=Region::find($id)->update([
            'name' => $request->name,
            'country' => $request->country,
            'state' => $request->state,
        ]);
        $response=[
            "message" => "Region Updated Successfully",
            'region' => $region,
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
    public function destroy($id)
    {
        Region::find($id)->update(['deleted'=>'1']);
        $response=[
            "message" => "Region Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
