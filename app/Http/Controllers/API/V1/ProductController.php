<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CreateRequest;
use App\Http\Resources\API\V1\Customer\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index()
    {
        $result = Product::with('categori', 'productimages', 'market', 'store', 'productreviews')->where('deleted', '0');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if (request()->input("gmpid") != null) {
            $search=request()->input("gmpid");
            $result->where('gmpid', $search);
        }
        if (request()->input("categoryid") != null) {
            $search=request()->input("categoryid");
            $result->where('category', $search);
        }
        if (request()->input("marketid") != null) {
            $search=request()->input("marketid");
            $result->where('marketid', $search);
        }
        if (request()->input("storeid") != null) {
            $search=request()->input("storeid");
            $result->where('storeid', $search);
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

        $products=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return ProductResource::collection($products);
        //return response()->json($products, 200);
    }

    public function getproductgroup(Request $request)
    {
        $result = Product::with('productimages', 'market', 'store');
        $products=$result->whereIn('id', $request->productlist)->get();
        return ProductResource::collection($products);
        //return response()->json($products, 200);
    }

    public function store(CreateRequest $request)
    {
        $user=auth()->user();
        $query=Product::where('name', $request->name)->where('storeid', $request->store)->first();
        if ($query) {
            return response()->json(["message" => 'Product Already created in this Store.', "status" => "error"], 400);
        }
        $marketquery=Store::where('id', $request->store)->first();
        $market=($marketquery)?$marketquery->marketid : null;
        $product = Product::create([
            'productid' => 'GMPP'.time(),
            'storeid' => $request->store,
            'marketid'=> $market,
            'gmpid' => $user->gmpid,
            'name' => $request->name,
            'category'=> $request->category,
            'amount'=> $request->price,
            'description'=> $request->description,
        ]);
        if ($images =$request->file('images')) {
            foreach ($images as $image) {
                $product->addMedia($image)->toMediaCollection('images');
            }
        }
        // for ($i=0; $i < count($request->file('images')); $i++) {
        //     if ($request->file('images')[$i]) {
        //         $file =$request->file('images')[$i];
        //         $extension = $file->getClientOriginalExtension();
        //         $filename = time().'.' . $extension;
        //         $file->move(public_path('uploads/productimages/'.$product->productid), $filename);
        //         $productimage= 'uploads/productimages/'.$filename;
        //     }else{
        //         $productimage=null;
        //     }
        //     $productimage = ProductImage::create([
        //         'productid' => $product->productid,
        //         'image' => $productimage,
        //     ]);
        // }
        $response=[
            "message" => "Product Created Successfully",
            'product' => new ProductResource($product),
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $product=Product::with('market', 'store', 'productimages', 'productreviews')->find($id);
        if (!$product) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Product found",
            'product' => new ProductResource($product),
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function update(CreateRequest $request, $id)
    {
        $product=Product::find($id);
        $query=Product::where('name', $request->name)->where('storeid', $request->store)->
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
            'amount'=> $request->price,
            'description'=> $request->description,
        ]);
        if ($images = $request->images) {
            $product->clearMediaCollection('images');
            foreach ($images as $image) {
                $product->addMedia($image)->toMediaCollection('images');
            }
        }
        $response=[
            "message" => "Product Updated Successfully",
            'product' => new ProductResource($product),
            "status" => "success"
        ];

        return response()->json($response, 200);
    }

    public function available(Request $request)
    {
        if(empty($request->productid)){
            return response()->json(["message" => "Product ID is required", "status" => "error"], 400);
        }
        $product=Product::where('id', $request->productid)->update([
            'status' => '0',
        ]);
        $response=[
            "message" => "Product is Available",
            'product' => $product,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }
    public function unavailable(Request $request)
    {
        if(empty($request->productid)){
            return response()->json(["message" => "Product ID is required", "status" => "error"], 400);
        }
        $product=Product::where('id', $request->productid)->update([
            'status' => '1',
        ]);
        $response=[
            "message" => "Product is Unavailable",
            'product' => $product,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function destroy($id)
    {
        // $productid=$product->productid;
        // $product->delete();
        // ProductImage::where('productid', $productid)->delete();
        $product = Product::find($id);
        if (!$product) {
            return response()->json(["message" => "Product not found", "status" => "error"], 400);
        }
        $product->update([
            'deleted' => '1',
        ]);
        $response=[
            "message" => "Product Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
