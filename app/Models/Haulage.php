<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Haulage extends Model
{
    use HasFactory;

    protected $fillable = [
        'gmpid',
        'orderid',
        'trackingid',
        'name',
        'phone',
        'inspectiondate',
        'region',
        'address',
        'description',
        'status',
        'rdate',
        'destination_region',
        'fromgmp',
        'solventapproved',
        'paymenttype',
        'paymentmethod',
        'user_guid',
        'who'
    ];
}
