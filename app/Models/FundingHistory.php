<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'fundingid',
        'gmpid',
        'amount',
        'ftime',
        'currency',
        'status',
        'type'
    ];
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
}
