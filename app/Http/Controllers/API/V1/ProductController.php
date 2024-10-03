<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CreateRequest;
use App\Http\Resources\API\V1\Customer\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Exception;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index()
    {
        $user=auth()->user();
        $result = Product::with('owner', 'categori', 'productimages', 'market', 'store', 'productreviews')
        ->where('gmpid', $user->gmpid)
        ->where('deleted', '0');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        // if (request()->input("gmpid") != null) {
        //     $search=request()->input("gmpid");
        //     $result->where('gmpid', "like", "%{$search}%");
        // }
        if (request()->input("store") != null) {
            $search=request()->input("store");
            $result->where(function ($query) use($search) {
                $query->whereHas('market', function ($query2) use($search){
                    $query2->where('name', "like", "%{$search}%")
                    ->orWhere('marketid', "like", "%{$search}%");
                })
                ->orWhereHas('store', function ($query2) use($search){
                    $query2->where('name', "like", "%{$search}%")
                    ->orWhere('storeid', "like", "%{$search}%");
                });
            });
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
        if (request()->input("approve") != null) {
            $search=request()->input("approve");
            $result->where('approved', $search);
        }
        if (request()->input("status") != null) {
            $search=request()->input("status");
            $result->where('status', $search);
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

    public function index2()
    {
        $result = Product::with('owner', 'categori', 'productimages', 'market', 'store', 'productreviews')->where('approved', '1')
        ->whereHas('owner', function ($query) {
            $query->where('seller', '1')->whereHas('subscription', function ($subQuery) {
                // Add any condition for subscription status if needed
                $subQuery->where('used', '0'); // Example: only active subscriptions
            });
        })
        ->where('deleted', '0');
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
        $products=$result->whereIn('id', $request->productlist)->where('approved', '1')->get();
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
        if ($marketquery->gmpid!=$user->gmpid) {
            return response()->json(["message" => 'Invalid Action', "status" => "error"], 400);
        }
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
            'quantity' => $request->quantity,
            'weight' => $request->weight,
            'height' => $request->height,
            'length' => $request->length,
            'width' => $request->width,
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
        $product=Product::with('owner', 'market', 'store', 'productimages', 'productreviews')->find($id);
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
            'quantity' => $request->quantity,
            'weight' => $request->weight,
            'height' => $request->height,
            'length' => $request->length,
            'width' => $request->width,
        ]);
        // if ($images = $request->images) {
        //     $product->clearMediaCollection('images');
        //     foreach ($images as $image) {
        //         $product->addMedia($image)->toMediaCollection('images');
        //     }
        // }

        $newImages = $request->images; // New images sent as files
        try {
            $removedImages = json_decode($request->get('removedImages'), true); // Links for exiting images
            $removedImages = $request->get('removedImages'); // Links for exiting images
            print_r($removedImages); exit();
            // Remove old images that are not in the existingImages array
            $mediaItems = $product->getMedia('images'); // Assuming interactsWithMedia is set up correctly
            foreach ($mediaItems as $mediaItem) {
                print_r($mediaItem->getUrl()); exit();
                if (in_array($mediaItem->getUrl(), $removedImages)) {
                    print_r($mediaItem->getUrl()); exit();
                    $mediaItem->delete(); // Delete images that no longer exist
                }
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Something went wrong', 'message' => $th->getMessage()], 400);
        }catch (Exception $e) {
            // Handle any other type of exception
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 400);
        }


        // Add any new images
        if ($newImages) {
            foreach ($newImages as $newImage) {
                $product->addMedia($newImage)->toMediaCollection('images'); // Add new images to the collection
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

    public function approve(Request $request)
    {
        $request->validate([
            'id' => 'required', 'itemcat'=>'required'
        ]);
        if(empty($request->id)){
            return response()->json(["message" => "Product ID is required", "status" => "error"], 400);
        }
        if($request->itemcat=='2'){
            if(empty($request->packagetype)){
                return response()->json(["message" => "Package Type is required", "status" => "error"], 400);
            }
        }
        $item=($request->itemcat=='1') ? '1' : $request->packagetype;
        $product=Product::where('id', $request->id)->update([
            'approved' => '1',
            'itemcat' => $request->itemcat,
            'packagetype' => $item,
        ]);
        $response=[
            "message" => "Product Approved",
            'product' => $product,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function topup(Request $request, $id)
    {
        if(empty($request->quantity)){
            return response()->json(["message" => "Top Up Quantity is required", "status" => "error"], 400);
        }
        $product=Product::find($id);
        $product->update([
            'quantity' => $product->quantity+$request->quantity,
        ]);
        $response=[
            "message" => "Quantity Topup Successfully",
            "status" => "success"
        ];

        return response()->json($response, 200);
    }
}
