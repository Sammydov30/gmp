<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticInfo extends Model
{
    use HasFactory;
    protected $table='shipment_info';
    protected $fillable = [
        "shipment_id",
        "type",
        "item",
        "name",
        "weighttype",
        "weight",
        "quantity",
        "length",
        "width",
        "height",
        "value_declaration"
    ];

}
