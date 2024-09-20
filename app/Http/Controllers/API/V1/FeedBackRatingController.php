<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedbackRatingRequest;
use App\Models\FeedBackRating;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class FeedBackRatingController extends Controller
{

    public function index()
    {
        $result = FeedBackRating::with('seller', 'order', 'item', 'customer');
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

    public function store(FeedbackRatingRequest $request)
    {
        $user=auth()->user();
        $query=FeedBackRating::where('orderid', $request->orderid)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $productid=Order::where('id', $request->orderid)->first()->products;
        $productid=explode("|", $productid)[0];
        $sellerid=Product::where('id', $productid)->first()->gmpid;
        $feedback = FeedBackRating::create([
            'orderid'=>$request->orderid,
            'sellerid'=>$sellerid,
            'gmpid'=>$user->gmpid,
            'itemid'=>$productid,
            'rate'=>$request->rate,
            'comment'=>$request->comment,
            'rdate'=>time(),
        ]);

        $response=[
            "message" => "Feedback Saved Successfully",
            'feedback' => $feedback,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $feedback=FeedBackRating::with('seller', 'order', 'item', 'customer')->find($id);
        if (!$feedback) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Feedback found",
            'feedback' => $feedback,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rate' => 'required',
            'comment' => 'required',
        ]);
        $user=auth()->user();
        $feedback=FeedBackRating::find($id);
        $order=Order::where('id', $feedback->orderid)->first();
        $checktime=25; // 1 Month
        if (($order->odate+$checktime)<time()) {
            return response()->json(["message" => 'Feedback cannot be given at this time', "status" => "error"], 400);
        }
        $query=FeedBackRating::where('orderid', $feedback->orderid)->where('gmpid', $user->gmpid)->
        where('id', '!=', $feedback->id)->first();
        if ($query) {
            return response()->json(["message" => 'Feedback already given', "status" => "error"], 400);
        }
        $feedback->update([
            'rate'=>$request->rate,
            'comment'=>$request->comment,
            'rdate'=>time(),
        ]);

        $response=[
            "message" => "Feedback Saved Successfully",
            'feedback' => $feedback,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(FeedBackRating $feedback)
    {
        $feedback->delete();
        $response=[
            "message" => "Feedback Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
