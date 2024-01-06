<?php

namespace App\Traits;
use App\Models\Notification;
use Illuminate\Http\Request;

trait NotificationTrait {

    /**
     * @param Request $request
     * @return $this|false|string
     */
    public function NotifyMe($title, $description, $type, $which) {
        $user=auth()->user();
        Notification::create([
            'gmpid' => $user->gmpid,
            'title'=>$title,
            'description'=>$description,
            'type'=>$type,
            'which'=>$which,
            'ntime'=> time(),
        ]);
    }

}
