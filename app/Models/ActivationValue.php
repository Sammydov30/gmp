<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationValue extends Model
{
    use HasFactory;
    protected $table="activation_values";
    protected $fillable = [
        'subscriptionamount',
        'subscriptionduration'
    ];
}
