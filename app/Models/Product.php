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
        'status',
        'deleted',

    ];

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

    public function toArray()
    {
        $array = parent::toArray();
        $array['posted'] = Carbon::parse('2024-08-13 09:00:00')->diffForHumans();
        return $array;
    }
}
