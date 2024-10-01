<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $fillable=[
        'productid',
        'storeid',
        'marketid',
        'gmpid',
        'name',
        'amount',
        'category',
        'description',
        'quantity',
        'weight',
        'height',
        'length',
        'width',
        'itemcat',
        'packagetype',
        'approved',
        'status',
        'deleted',

    ];

    public function owner()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid')->with('subscription');
    }
    public function categori()
    {
        return $this->belongsTo(Category::class, 'category');
    }
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
    public function productreviews()
    {
        return $this->hasMany(FeedBackRating::class, 'itemid', 'id');
    }
}
