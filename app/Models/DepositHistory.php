<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'depositid',
        'gmpid',
        'amount',
        'wtime',
        'currency',
        'inprogress',
        'status'
    ];
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
}
