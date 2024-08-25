<?php

namespace App\Http\Resources\API\V1\Customer;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        ];
    }
}
