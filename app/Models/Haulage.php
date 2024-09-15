<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Haulage extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_guid',
        'gmpid',
        'orderid',
        'trackingid',
        'name',
        'phone',
        'inspectiondate',
        'region',
        'address',
        'description',
        'status',
        'rdate',
        'destination_region',
        'fromgmp',
        'solventapproved',
        'paymenttype',
        'paymentmethod',
        'user_guid',
        'who'
    ];

    public function toArray()
    {
        $array = parent::toArray();
        switch($this->status) {
          case '1':
            $row['statustype']='Registered';
            $row['statuscode']='1';
            break;
          case '2':
            $row['statustype']='Ready to Go';
            $row['statuscode']='2';
            break;
          case '3':
            $row['statustype']='Delivered';
            $row['statuscode']='3';
            break;
          default:
            $row['statustype']='Registered';
            $row['statuscode']='1';
        }
        if ($this->cancel=='1') {
            $row['statustype']='Cancelled';
            $row['statuscode']='4';
            $row['status']='4';
        }
        $array += $row;

        return $array;
    }
}
