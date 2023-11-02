<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;
    protected $table="prescriptions";
    protected $fillable = [
        'prescriptionid',
        'patientid',
        'doctorid',
        'appointmentid',
        'type',
        'title',
        'dosage',
        'description',
        'ptime',
        'status',
    ];

    public function toArray()
    {
        $array = parent::toArray();
        $array['ddate'] = @gmdate('d/m/y h:i a', $this->ptime);
        return $array;
    }


    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'doctorid', 'doctorid')->select('doctorid', 'firstname', 'lastname');
    }

    public function patient()
    {
        return $this->hasOne(Customer::class, 'patientid', 'patientid')->select('patientid', 'firstname', 'lastname');
    }
}
