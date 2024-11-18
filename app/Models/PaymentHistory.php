<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasFactory;
    protected $table='payment_histories';
    protected $fillable = [
        "id", "reference", "shipmentid", "shipmenttype", "amount", "email", "customername",
        "narration", "paymentprovider", "pdate", "status"
    ];

}
