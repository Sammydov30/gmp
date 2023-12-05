<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logistic extends Model
{
    use HasFactory;
    protected $fillable = [
        "pickupvehicle",
        "gmpid",
        "pickupdate",
        "gmppayment",
        "p_status",
        "deliverymode",
        "pickupcenter",
        "cname",
        "cphone",
        "caddress",
        "rname",
        "rphone",
        "raddress",
        "fromregion",
        "toregion",
        "totalweight",
        "amount",
    ];

}
