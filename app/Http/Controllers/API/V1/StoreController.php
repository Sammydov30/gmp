<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CreateRequest;
use App\Models\Store;

class StoreController extends Controller
{

    public function index()
    {
        $result = Store::with('market')->withCount('products')->where('deleted', '0');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("gmpid") != null) {
            $search=request()->input("gmpid");
            $result->where('gmpid', $search);
        }
        if (request()->input("marketid") != null) {
            $search=request()->input("marketid");
            $result->where('marketid', $search);
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
        return response()->json($park, 200);
    }

    public function store(CreateRequest $request)
    {
        $user=auth()->user();
        $query=Store::where('name', "like", "%{$request->name}%")->where('marketid', $request->market)->first();
        if ($query) {
            return response()->json(["message" => 'Store Already created in this Market.', "status" => "error"], 400);
        }
        $store = Store::create([
            'storeid' => 'GMPS'.time(),
            'marketid'=> $request->market,
            'gmpid' => $user->gmpid,
            'name' => $request->name,
            'category'=> $request->category,
            'phone'=> $request->phone,
            'website' => $request->website,
        ]);

        $response=[
            "message" => "Store Created Successfully",
            'store' => $store,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $store=Store::with('market')->withCount('products')->find($id);
        if (!$store) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Store found",
            'store' => $store,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function update(CreateRequest $request, $id)
    {
        $store=Store::find($id);
        $query=Store::where('name', "like", "%{$request->name}%")->where('marketid', $request->market)->
        where('id', '!=', $store->id)->first();
        if ($query) {
            return response()->json(["message" => 'Store Already created in this Market.', "status" => "error"], 400);
        }
        $store->update([
            'marketid'=> $request->market,
            'name' => $request->name,
            'category'=> $request->category,
            'phone'=> $request->phone,
            'website' => $request->website,
        ]);
        $response=[
            "message" => "Store Updated Successfully",
            'store' => $store,
            "status" => "success"
        ];

        return response()->json($response, 200);
    }

    public function destroy($id)
    {
        $store=Store::find($id);
        if (!$store) {
            return response()->json(["message" => "Store not found", "status" => "error"], 400);
        }
        $store->update([
            'deleted' => '1',
        ]);
        $response=[
            "message" => "Store Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
