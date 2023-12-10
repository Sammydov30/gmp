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
        'address',
        'location',
        'city'
    ];
}
