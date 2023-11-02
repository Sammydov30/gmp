<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;
    protected $table="appointments";
    protected $fillable = [
        'appointmentid',
        'patientid',
        'doctorid',
        'atime',
        'locality',
        'checkups',
        'status',
    ];

    public function toArray()
    {
        $array = parent::toArray();
        $array['stime'] = @gmdate('h:i a', $this->atime+3600);
        $array['ddate'] = @gmdate('d/m/y', $this->atime);
        return $array;
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'doctorid', 'doctorid');
    }

    public function patient()
    {
        return $this->hasOne(Customer::class, 'patientid', 'patientid');
    }
}
