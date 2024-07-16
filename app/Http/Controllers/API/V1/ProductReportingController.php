<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductReportingRequest;
use App\Models\Product;
use App\Models\ProductReport;

class ProductReportingController extends Controller
{

    public function index()
    {
        $result = ProductReport::with('seller', 'item', 'customer');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("sellerid") != null) {
            $sellerid=request()->input("sellerid");
            $result->where('sellerid', $sellerid);
        }
        if (request()->input("gmpid") != null) {
            $gmpid=request()->input("gmpid");
            $result->where('gmpid', $gmpid);
        }
        if (request()->input("itemid") != null) {
            $itemid=request()->input("itemid");
            $result->where('itemid', $itemid);
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

    public function store(ProductReportingRequest $request)
    {
        $user=auth()->user();
        $sellerid=Product::where('id', $request->itemid)->first()->gmpid;
        $productreport = ProductReport::create([
            'sellerid'=>$sellerid,
            'gmpid'=>$user->gmpid,
            'itemid'=>$request->productid,
            'reason'=>$request->reason,
            'description'=>$request->description,
            'rdate'=>time(),
        ]);

        $response=[
            "message" => "Report Saved Successfully",
            'report' => $productreport,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $report=ProductReport::with('seller', 'item', 'customer')->find($id);
        if (!$report) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Report found",
            'report' => $report,
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
    public function destroy(ProductReport $report)
    {
        $report->delete();
        $response=[
            "message" => "Report Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
