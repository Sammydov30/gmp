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
        'type',
        'which'
    ];
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
    public function withdrawal()
    {
        return $this->hasOne(WithdrawalHistory::class, 'id', 'fundingid');
    }
    public function deposit()
    {
        return $this->hasOne(DepositHistory::class, 'id', 'fundingid');
    }
    public function logistic()
    {
        return $this->hasOne(Logistic::class, 'id', 'fundingid');
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'fundingid');
    }
}
