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
        'status',
        'deleted',
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
        $array['categoryname'] = $this->GetCategoryNames($this->category);
        return $array;
    }

    private function GetCategoryNames($categories) {
        $category=explode(",", $categories);
        $expcat=[];
        foreach ($category as $key) {
            $each=Category::where('id', $key)->first();
            if ($each) {
                array_push($expcat, $each->name);
            }
        }
        return implode(",", $expcat);

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
