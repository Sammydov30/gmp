<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;
    protected $fillable=[
        'storeid',
        'marketid',
        'gmpid',
        'name',
        'category',
        'phone',
        'website',
        'open',
        'status'
    ];

    public function market()
    {
        return $this->belongsTo(MarketPlace::class, 'marketid');
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'storeid', 'id');
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['categories'] = $this->GetCategoriesDetails($this->category);
        return $array;
    }

    private function GetCategoriesDetails($categories){
        $cat=explode(',', $categories);
        $category=Category::whereIn('id', $cat)->get();
        if (!$category) {
            return null;
        }
        return $category;
    }

}
