<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketPlace extends Model
{
    use HasFactory;
    protected $fillable=[
        'marketid',
        'name',
        'location',
        'state',
        'region',
        'open',
        'status'
    ];

    public function region()
    {
        return $this->belongsTo(Region::class, 'region');
    }

}
