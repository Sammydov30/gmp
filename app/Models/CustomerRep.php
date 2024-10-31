<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class CustomerRep extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table ="representatives";
    protected $fillable = [
        'name',
        'email',
        'password',
    ];


}
