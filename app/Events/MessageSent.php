<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $chatSessionId;

    public function __construct(array $data)
    {
        $this->message = $data['message'];
        $this->chatSessionId = $data['conversationid'];
    }



    public function broadcastOn()
    {
        // Ensure channel name is correct and matches your client-side subscription
        return new Channel('chat.' . $this->chatSessionId);
    }

    public function broadcastWith()
    {
        return [
            'conversation' => $this->chatSessionId,
            'details' => $this->message,
        ];
    }
}


