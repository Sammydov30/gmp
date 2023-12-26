<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable=[
        'productid',
        'storeid',
        'marketid',
        'gmpid',
        'name',
        'category',
        'description',
        'status'
    ];

    public function market()
    {
        return $this->belongsTo(MarketPlace::class, 'marketid');
    }
    public function store()
    {
        return $this->belongsTo(Store::class, 'storeid');
    }
    public function productimages()
    {
        return $this->hasMany(ProductImage::class, 'productid', 'productid');
    }
}
