<?php

namespace App\Http\Resources\API\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource2 extends JsonResource
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
            'amount' => $this->amount,
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'category' => $this->category,
            'description' => $this->description,
            'status' => $this->status,
            'market'   => $this->market,
            'store' => $this->store,
            'images' => ProductImagesResource::collection($this->getMedia('images')),
        ];
    }
}
