<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CreateRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;

class ProductController extends Controller
{

    public function index()
    {
        $result = Product::with('productimages', 'market', 'store');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("gmpid") != null) {
            $search=request()->input("gmpid");
            $result->where('gmpid', $search);
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

        $park=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($park, 200);
    }

    public function store(CreateRequest $request)
    {
        $user=auth()->user();
        $query=Product::where('name', "like", "%{$request->name}%")->where('storeid', $request->store)->first();
        if ($query) {
            return response()->json(["message" => 'Product Already created in this Store.', "status" => "error"], 400);
        }
        $market=Store::where('id', $request->store)->first()->marketid;
        $product = Product::create([
            'productid' => 'GMPP'.time(),
            'storeid' => $request->store,
            'marketid'=> $market,
            'gmpid' => $user->gmpid,
            'name' => $request->name,
            'category'=> $request->category,
            'description'=> $request->description,
        ]);

        if ($request->file('productimage')) {
            $file =$request->file('productimage');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/productimage/'.$product->productid), $filename);
            $productimage= 'uploads/productimage/'.$filename;
        }else{
            $productimage=null;
        }
        $productimage = ProductImage::create([
            'productid' => $product->productid,
            'image' => $productimage,
        ]);
        $response=[
            "message" => "Product Created Successfully",
            'product' => $product,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $product=Product::with('market', 'store', 'productimages')->find($id);
        if (!$product) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Product found",
            'product' => $product,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function update(CreateRequest $request, Product $product)
    {
        $query=Product::where('name', "like", "%{$request->name}%")->where('storeid', $request->store)->
        where('id', '!=', $product->id)->first();
        if ($query) {
            return response()->json(["message" => 'Product Already created in this Market.', "status" => "error"], 400);
        }
        $market=Store::where('id', $request->store)->first()->marketid;
        $product->update([
            'storeid' => $request->store,
            'marketid'=> $market,
            'name' => $request->name,
            'category'=> $request->category,
            'description'=> $request->description,
        ]);
        $response=[
            "message" => "Product Updated Successfully",
            'product' => $product,
            "status" => "success"
        ];

        return response()->json($response, 200);
    }

    public function destroy(Product $product)
    {
        $productid=$product->productid;
        $product->delete();
        ProductImage::where('productid', $productid)->delete();
        $response=[
            "message" => "Product Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
