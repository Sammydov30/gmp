<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SpecialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpecialItemsController extends Controller
{

    public function index()
    {
        $result = SpecialItem::where('deleted', '0')->where('status', '0');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
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

        $data=$result->orderBY($sortBy, $sortOrder)->get();

        $dataset = array(
            "echo" => 1,
            "totalrecords" => count($data),
            "totaldisplayrecords" => count($data),
            "data" => $data
          );
          echo json_encode($dataset);

        //$data=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($dataset, 200);
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

    public function store(Request $request)
    {
        // $query=Region::where('name', "like", "%{$request->name}%")->where('country', $request->country)->first();
        // if ($query) {
        //     return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        // }
        $request->validate([
            'name' => 'required',
            'svalue'=>'required|numeric'
        ]);
        $region = SpecialItem::create([
            'entity_guid'=>Str::uuid(),
            'name' => $request->name,
            'svalue' => $request->svalue,
        ]);

        $response=[
            "message" => "Special Item Created Successfully",
            'region' => $region,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $region=SpecialItem::find($id);
        if (!$region) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Item found",
            'item' => $region,
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
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'svalue'=>'required|numeric'
        ]);
        $region=SpecialItem::find($id)->update([
            'name' => $request->name,
            'svalue' => $request->svalue,
        ]);
        $response=[
            "message" => "Item Updated Successfully",
            'item' => $region,
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
        SpecialItem::find($id)->update(['deleted'=>'1']);
        $response=[
            "message" => "Item Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
