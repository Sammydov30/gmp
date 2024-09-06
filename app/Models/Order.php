<?php

namespace App\Models;

use App\Http\Resources\API\V1\Customer\ProductResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'orderid',
        'customer',
        'products',
        'address',
        'phone',
        'region',
        'odate',
        'orderamount',
        'servicefee',
        'totalamount',
        'tx_ref',
        'currency',
        'p_status',
        'paymentmethod',
        'deliverymode',
        'placedtime',
        'deliverytime',
        'pickeduptime',
        'acceptedtime',
        'readytime',
        'status'
    ];
    public function toArray()
    {
        $array = parent::toArray();
        $array['ordertime'] = gmdate('d-m, y h:ia', $this->odate);
        $array['placedtime'] = @gmdate('d-m, y h:ia', strtotime($this->placedtime));
        $array['deliverytime'] = @gmdate('d-m, y h:ia', strtotime($this->deliverytime));
        $array['productdetails'] = $this->GetOrderDetails($this->products);
        $array['ongoing'] = ($this->status=='4') ? '2' : '1';
        return $array;
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'customer');
    }


    //GET PRODUCT DETAILS
    private function GetOrderDetails($products){
        $productdetails=array();
        $items=explode(",", $products);
        foreach ($items as $item => $value) {
            $pp=explode("|", $value);
            $pt=[
                "quantity"=>$pp[1],
                "productlist" => $this->GetProductDetails($pp[0]),
            ];
            array_push($productdetails, $pt);
        }
        return $productdetails;
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
