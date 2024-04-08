<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    protected $table="states";
    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    public function regions()
    {
        return $this->hasMany(Region::class, 'state');
    }
}
