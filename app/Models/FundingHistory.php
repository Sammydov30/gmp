<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'fundingid',
        'gmpid',
        'amount',
        'ftime',
        'currency',
        'status',
        'type',
        'which'
    ];
    public function customer()
    {
        return $this->hasOne(Customer::class, 'gmpid', 'gmpid');
    }
    public function withdrawal()
    {
        return $this->hasOne(WithdrawalHistory::class, 'id', 'fundingid');
    }
    public function deposit()
    {
        return $this->hasOne(DepositHistory::class, 'id', 'fundingid');
    }
    public function logistic()
    {
        return $this->hasOne(Shipment::class, 'id', 'fundingid');
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'fundingid');
    }

    public function toArray()
    {
        $array = parent::toArray();
        //$array['activename'] = ($this->active=='1')? "Online" : 'Offline';
        $which=[];
        $whichname="";
        switch ($this->which) {
            case '1':
                $which=($this->type=='1') ? $this->deposit : $this->withdrawal;
                $whichname = "Deposit/Withdrawal";
                break;
            case '2':
                $which=$this->logistic;
                $whichname = "Logistics";
                break;
            case '3':
                $which=$this->order;
                $whichname = "Orders";
                break;
            default:
                $which=$which;
                $whichname = "Unknown";
                break;
        }
        $array['typenamee'] = ($this->type=='1') ? 'Credit' : 'Debit';
        $array['details'] = $which;
        $array['whichname'] = $whichname;
        $array['date']   = $this->created_at->toDateString();
        $array['time']   = $this->created_at->toTimeString();
        return $array;
    }
}
