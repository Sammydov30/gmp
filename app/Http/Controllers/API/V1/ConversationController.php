<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Representative;
use App\Models\CustomerRep;
use App\Events\NotifyRepresentative;
use App\Events\ConversationEnded;
use App\Http\Requests\CreateConversationRequest;

class ConversationController extends Controller
{
    //
    public function startConversation(CreateConversationRequest $request)
    {
        // Get all representatives and select one at random
        $representative = CustomerRep::inRandomOrder()->first();

        if (!$representative) {
            return response()->json(['message' => 'No representatives available'], 404);
        }

        // Create a conversation with a randomly chosen representative
        $conversation = Conversation::create([
            'participant_id' => $this->generateUniqueId(),
            'representative_id' => $representative->id,
            'status' => 'open'
        ]);

        // Store the initial message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'message' => $request->message,
            'image' => null,
            'sender_name' => $conversation->participant_id,
        ]);

        // Broadcast notification to rep
        event(new NotifyRepresentative([
            'repid' => $representative->id,
            'message' => 'You have been assigned a new conversation',
        ]));


        return response()->json([
            'status' => 'success',
            'message' => 'Conversation started',
            'conversationID' => $conversation->id,
            'participantID' => $conversation->participant_id
        ]);
    }


    // End a conversation
    public function endConversation($conversationId)
    {
        // Find the conversation
        $conversation = Conversation::where('id', $conversationId)->first();

        if ($conversation && $conversation->status == 'open') {
            $conversation->status = 'closed';
            $conversation->save();

            // Broadcast notification via Pusher
            event(new ConversationEnded([
                'conversationid' => $conversation->id,
                'message' => 'Conversation closed',
            ]));

            return response()->json(['message' => 'Conversation closed']);
        }

        return response()->json(['message' => 'Conversation not found or already closed'], 404);
    }

    //fetch rep conversations
    public function fetchRepConversation($repID)
    {
        // Fetch all conversations for the representative
        $conversations = Conversation::where('representative_id', $repID)
            ->where('status', 'open')
            ->get();

        $conversationDetails = [];

        foreach ($conversations as $conversation) {
            // Fetch the last message for each conversation (ordered by latest `created_at`)
            $lastMessage = Message::where('conversation_id', $conversation->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Append conversation and the last message to the result
            $conversationDetails[] = [
                'conversation' => $conversation,
                'last_message' => $lastMessage
            ];
        }

        return $conversationDetails;
    }



    private function generateUniqueId()
    {
        $prefix = 'GSupport_';
        $randomString = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
        return $prefix . $randomString;
    }

}
