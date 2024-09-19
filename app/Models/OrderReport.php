<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReport extends Model
{
    use HasFactory;
    protected $table = 'orderreports';
    protected $fillable = [
        'orderid',
        'sellerid',
        'gmpid',
        'itemid',
        'comment',
        'rdate',
        'deleted'
    ];
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
    public function seller()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'sellerid');
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'orderid');
    }
    public function item()
    {
        return $this->hasOne(Product::class, 'id', 'itemid');
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['reviewdate'] = gmdate('d-m-Y', $this->rdate);
        return $array;
    }
}
