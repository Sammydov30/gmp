<?php

namespace App\Models;

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
        'status'
    ];
    public function toArray()
    {
        $array = parent::toArray();
        $array['ordertime'] = gmdate('d-m, y h:ia', $this->odate);
        return $array;
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer');
    }
}
