<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;
    protected $table="regions";
    protected $fillable = [
        'entity_guid',
        'country',
        'state',
        'name',
        'status',
        'deleted'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state');
    }
}
