<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NotifyRepresentative implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $repID;

    public function __construct(array $data)
    {
        $this->message = $data['message'];
        $this->repID = $data['repid'];
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        // Ensure channel name is correct and matches your client-side subscription
        return [
            new Channel('notification.' . $this->repID),
        ];
    }


    public function broadcastWith()
    {
        return [
            'repid' => $this->repID,
            'details' => $this->message,
        ];
    }
}
