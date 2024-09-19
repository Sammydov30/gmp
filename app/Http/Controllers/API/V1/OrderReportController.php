<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderReportRequest;
use App\Models\OrderReport;
use App\Models\Order;

class OrderReportController extends Controller
{

    public function index()
    {
        $result = OrderReport::with('seller', 'order', 'item', 'customer');
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
        if (request()->input("orderid") != null) {
            $orderid=request()->input("orderid");
            $result->where('orderid', $orderid);
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

    public function store(OrderReportRequest $request)
    {
        $query=OrderReport::where('orderid', $request->orderid)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $order=Order::where('id', $request->orderid)->first();
        $productid=$order->products;
        $productid=explode("|", $productid)[0];
        $sellerid=$order->sellerid;
        $feedback = OrderReport::create([
            'orderid'=>$request->orderid,
            'sellerid'=>$sellerid,
            'gmpid'=>$order->customer,
            'itemid'=>$productid,
            'reason'=>$request->reason,
            'comment'=>$request->comment,
            'rdate'=>time(),
        ]);

        $response=[
            "message" => "Report Saved Successfully",
            'feedback' => $feedback,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $feedback=OrderReport::with('seller', 'order', 'item', 'customer')->find($id);
        if (!$feedback) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Report found",
            'feedback' => $feedback,
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
    public function destroy(OrderReport $feedback)
    {
        $feedback->delete();
        $response=[
            "message" => "Report Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
