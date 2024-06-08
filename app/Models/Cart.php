<?php

namespace App\Models;

use App\Http\Resources\API\V1\Customer\ProductResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'product',
        'quantity',
        'customer',
        'description',
        'availability',
        'confirmed',
    ];

    public function toArray()
    {
        $array = parent::toArray();
        //$array['item'] = $this->GetProductDetails($this->product);

        return $array;
    }

    public function item()
    {
        return $this->hasOne(Product::class, 'id', 'product');
    }
    private function GetProductDetails($item){
        $product=Product::with('market', 'store', 'productimages')->find($item);
        if (!$product) {
            return null;
        }
        $product = new ProductResource($product);
        //Convert to json then to array (To get Pure array)
        //$item=json_decode(json_encode($productt), true);
        //print_r($item); exit();
        return $product;
    }


}
