<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;
    protected $table="addressbook";
    protected $fillable = [
        'gmpid',
        'firstname',
        'lastname',
        'phonenumber',
        'address',
        'location',
        'city',
        'status'
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
    public function locationdata()
    {
        return $this->hasOne(Region::class, 'id', 'location')->with('country')->select('id', 'name');
    }
}
