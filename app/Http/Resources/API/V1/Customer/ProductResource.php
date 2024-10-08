<?php

namespace App\Http\Resources\API\V1\Customer;

use App\Models\Category;
use App\Models\FeedBackRating;
use App\Models\Product;
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
            'productid' => $this->productid,
            'storeid' => $this->storeid,
            'marketid' => $this->marketid,
            'gmpid' => $this->gmpid ,
            'name' => $this->name,
            'price' => $this->amount,
            'categoryid' => $this->category,
            'categoryname' => $this->GetCategoryNames($this->category),
            'description' => $this->description,
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'approved' => $this->approved,
            'status' => $this->status,
            'posted' => Carbon::parse($this->created_at)->diffForHumans(),
            'postdate' => Carbon::parse($this->created_at)->format('d-m-Y'),
            'owner' => $this->owner,
            'category' => $this->categori,
            'market'   => $this->market,
            'store' => $this->store,
            'images' => ProductImagesResource::collection(@$this->getMedia('images')),
            'productreviews' => $this->productreviews,
            'groupedRatings' => $this->getGroupedreview($this->id)
        ];
    }

    private function GetCategoryNames($categories) {
        $category=explode(",", $categories);
        $expcat=[];
        foreach ($category as $key) {
            $each=Category::where('id', $key)->first();
            if ($each) {
                array_push($expcat, $each->name);
            }
        }
        return implode(",", $expcat);

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
        for ($i=5; $i > 0; $i--) {
            $found=false;
            foreach ($ratings as $rating) {
                if ($i==$rating->rate) {
                    $groupedRatings[$rating->rate] = [
                        'rate' => str($i),
                        'count' => $rating->count,
                        'percentage' => round(($rating->count / $totalReviews) * 100)
                    ];
                    $found=true;
                    break;
                }
            }
            if (!$found) {
                $groupedRatings[] = [
                    'rate' => str($i),
                    'count' => '0',
                    'percentage' => '0',
                ];
            }
        }

        // Output the result
        return $groupedRatings;

    }
}
