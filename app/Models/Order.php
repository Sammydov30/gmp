<?php

namespace App\Models;

use App\Http\Resources\API\V1\Customer\ProductResource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'orderid',
        'customer',
        'sellerid',
        'storeid',
        'trackingid',
        'products',
        'address',
        'phone',
        'region',
        'odate',
        'orderamount',
        'servicefee',
        'totalamount',
        'totalweight',
        'tx_ref',
        'currency',
        'p_status',
        'paymentmethod',
        'logisticsprovider',
        'deliverymode',
        'placedtime',
        'deliverytime',
        'pickuptime',
        'acceptedtime',
        'readytime',
        'status'
    ];
    public function items(){
        return $this->hasMany(OrderItem::class, 'orderid', 'orderid');
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['ordertime'] = gmdate('d-m, y h:ia', $this->odate); //($this->odate==null) ? $this->odate : @Carbon::parse($this->odate)->format('jS F Y h:ia');
        $array['placedtime'] =($this->placedtime==null) ? $this->placedtime : @Carbon::parse($this->placedtime)->format('jS F Y,h:ia');
        $array['deliverytime'] = ($this->deliverytime==null) ? $this->deliverytime: @Carbon::parse($this->deliverytime)->format('jS F Y,h:ia');
        $array['productdetails'] = $this->GetOrderDetails($this->products);
        $array['ongoing'] = ($this->status=='4') ? '2' : '1';
        return $array;
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'customer');
    }
    public function review()
    {
        return $this->hasOne(FeedBackRating::class, 'orderid', 'id')->with('customer');
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
