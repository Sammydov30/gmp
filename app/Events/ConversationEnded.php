<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ConversationEnded implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationID;

    public function __construct(array $data)
    {
        $this->message = $data['message'];
        $this->conversationID = $data['conversationid'];
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
            new Channel('close.' . $this->conversationID),
        ];
    }


    public function broadcastWith()
    {
        return [
            'conversation' => $this->conversationID,
            'details' => $this->message,
        ];
    }
}
