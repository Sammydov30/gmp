<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupCenter extends Model
{
    use HasFactory;
    protected $table="branches";
    protected $fillable = [
        'entity_guid',
        'state',
        'name',
        'phone',
        'email',
        'address',
        'status',
        'deleted'
    ];

    public function region()
    {
        return $this->belongsTo(Region::class, 'state');
    }
}
