<?php

namespace App\Http\Resources\API\V1\Customer;

use App\Models\FeedBackRating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      =>  $this->id,
            'storeid' => $this->storeid,
            'marketid' => $this->marketid,
            'gmpid' => $this->gmpid ,
            'name' => $this->name,
            'price' => $this->amount,
            'categoryid' => $this->category,
            'description' => $this->description,
            'status' => $this->status,
            'posted' => Carbon::parse($this->created_at)->diffForHumans(),
            'category' => $this->categori,
            'market'   => $this->market,
            'store' => $this->store,
            'images' => ProductImagesResource::collection($this->getMedia('images')),
            'productreviews' => $this->productreviews,
            'groupedRatings' => $this->getGroupedreview($this->id)
        ];
    }

    //GET ORDER REVIEW
    // private function GetOrders($product){
    //     //Get order with product
    //     $orderlist=array();
    //     $items=explode(",", $products);
    //     foreach ($items as $item => $value) {
    //         $pp=explode("|", $value);
    //         $pt=[
    //             "quantity"=>$pp[1],
    //             "productlist" => $this->GetProductDetails($pp[0]),
    //         ];
    //         array_push($productdetails, $pt);
    //     }
    //     return $productdetails;
    // }
    // private function GetProductDetails($item){
    //     $product=Product::with('market', 'store', 'productimages')->find($item);
    //     if (!$product) {
    //         return null;
    //     }
    //     $product = new ProductResource($product);
    //     //Convert to json then to array (To get Pure array)
    //     //$item=json_decode(json_encode($productt), true);
    //     //print_r($item); exit();
    //     return $product;
    // }
    private function getGroupedreview($product){
        $ratings = FeedBackRating::select('rate', DB::raw('COUNT(rate) as count'))->where('itemid', $product)
        ->groupBy('rate')
        ->get();

        // Get the total number of reviews
        $totalReviews = $ratings->sum('count');

        // Calculate the percentage for each rating
        $groupedRatings = [];
        foreach ($ratings as $rating) {
            $groupedRatings[$rating->rating] = [
                'count' => $rating->count,
                'percentage' => round(($rating->count / $totalReviews) * 100, 2)
            ];
        }

        // Output the result
        return $groupedRatings;

    }
}
