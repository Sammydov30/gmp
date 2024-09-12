<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialItem extends Model
{
    use HasFactory;
    protected $table="package_type";
    protected $fillable = [
        'entity_guid',
        'name',
        'svalue',
        'description',
        'status',
        'deleted'
    ];
}
