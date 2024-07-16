<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedBackRating extends Model
{
    use HasFactory;
    protected $table = 'feedbackrating';
    protected $fillable = [
        'orderid',
        'sellerid',
        'gmpid',
        'itemid',
        'rate',
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
}
