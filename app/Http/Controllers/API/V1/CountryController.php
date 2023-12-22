<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Country\CreateRequest;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegionsController extends Controller
{

    public function index()
    {
        $result = DB::table('countries');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if ((request()->input("sortBy")!=null) && in_array(request()->input("sortBy"), ['id', 'created_at'])) {
            $sortBy=request()->input("sortBy");
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

    public function store(CreateRequest $request)
    {
        $query=Country::where('name', "like", "%{$request->name}%")->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $country = Region::create([
            'name' => $request->name,
        ]);

        $response=[
            "message" => "Country Created Successfully",
            'country' => $country,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $country=Country::find($id);
        if (!$country) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Country found",
            'country' => $country,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function update(CreateRequest $request, Country $country)
    {
        $query=Country::where('name', "like", "%{$request->name}%")->
        where('id', '!=', $country->id)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $country->update([
            'name' => $request->name,
        ]);
        $response=[
            "message" => "Country Updated Successfully",
            'country' => $country,
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
    public function destroy(Country $country)
    {
        $country->delete();
        $response=[
            "message" => "Country Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
