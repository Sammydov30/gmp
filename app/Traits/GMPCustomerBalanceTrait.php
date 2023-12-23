<?php

namespace App\Traits;

use App\Models\Customer;
use Illuminate\Http\Request;

trait GMPCustomerBalanceTrait {

    public function checkWallet($amount) {
        $user=auth()->user();
        $check=Customer::where('gmpid', $user->gmpid)->first();
        if ($check) {
            $balance=$check->ngnbalance;
            if ($balance<$amount) {
                return false;
            }
        }else{
            return false;
        }
        return true;
    }

    public function chargeWallet($amount) {
        $user=auth()->user();
        $check=Customer::where('gmpid', $user->gmpid)->first();
        $balance=$check->ngnbalance;
        $newbal=$balance-$amount;
        Customer::where('gmpid', $user->gmpid)->update(['ngnbalance'=>$newbal]);
    }

}
