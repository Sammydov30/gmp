<?php

namespace App\Traits;

use App\Models\Subscription;

trait ActiveSubscriptionTrait {

    public function activeSubscription() {
        $user=auth()->user();
        $check=Subscription::where("gmpid", $user->gmpid)->latest()->first();
        $currtime=time();
        if ($check) {
            if ($currtime<$check->expiredtime) {
                return true;
            }
        }
        return false;
    }

}
