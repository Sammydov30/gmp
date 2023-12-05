<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gmpid',
        'firstname',
        'lastname',
        'othername',
        'email',
        'verifytoken',
        'email_verified_at',
        'is_verified',
        'loginid',
        'phone',
        'password',
        'profilepicture',
        'token',
        'seller',
        'sellerid',
        'status',
        'otp',
        'otpexpiration',
        'address',
        'lga',
        'city',
        'country',
        'state',
        'dob',
        'gender',
        'active',
        'deleted',
        'deactivated',
        'emailnotify',
        'desktopnotify',
        'subscriptiondue',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function toArray()
    {
        $array = parent::toArray();
        $array['name'] = $this->firstname.' '.$this->lastname;
        $array['cdate'] = @gmdate('d/m/y', strtotime($this->created_at));
        $array['ctime'] = @gmdate('h:i a', strtotime($this->created_at));
        $url = env('APP_UURL');
        $array['profilepicture'] =($this->profilepicture=="https://res.cloudinary.com/examqueat/image/upload/v1664654734/handshake.jpg")? $this->profilepicture :
        $url.$this->profilepicture;
        $array['activename'] = ($this->active=='1')? "Online" : 'Offline';
        return $array;
    }



}

