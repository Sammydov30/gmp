<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'withdrawalid',
        'gmpid',
        'amount',
        'accountname',
        'accountnumber',
        'bank',
        'bankname',
        'wtime',
        'currency',
        'status'
    ];
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
}
