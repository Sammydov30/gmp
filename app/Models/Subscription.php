<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
    protected $fillable = [
        'plan',
        'customer',
        'currtime',
        'expiredtime',
        'ra',
        'used',
        'checkups',
        'status'
    ];
}
