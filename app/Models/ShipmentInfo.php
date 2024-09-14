<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentInfo extends Model
{
    use HasFactory;
    protected $table='shipment_info';
    protected $fillable = [
        "entity_guid",
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
