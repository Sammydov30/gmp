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

}
