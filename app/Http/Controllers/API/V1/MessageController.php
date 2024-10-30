<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;
use App\Http\Requests\SendMessageRequest;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\CustomerRep;

class MessageController extends Controller
{
    public function sendMessage(SendMessageRequest $request, $conversationId)
    {
        // Find the open conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where('status', 'open')
            ->first();

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found or closed'], 404);
        }

        // Determine sender details
        list($senderType, $senderName) = $this->getSenderDetails($request, $conversation);

        // Handle image upload if provided
        $imagePath = $this->handleFileUpload($request, 'image', 'message_images');

        // Create the message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'message' => $request->message,
            'image' => $imagePath,
        ]);

        // Broadcast the message via Pusher
        event(new MessageSent([
            'conversationid' => $conversationId,
            'message' => $message,
        ]));

        return response()->json([
            'message' => 'Message sent',
            'data' => $this->formatMessageResponse($message)
        ]);
    }

    private function getSenderDetails($request, $conversation)
    {
        switch ($request->who) {
            case 'customer':
                return ['customer', $conversation->participant_id];

            case 'rep':
                $sender = CustomerRep::find($conversation->representative_id);
                if (!$sender) {
                    return response()->json(['message' => 'Representative not found'], 404);
                }
                return ['rep', $sender->name];

            default:
                return response()->json([
                    "message" => "Invalid Type: " . $request->who,
                    "status" => "error"
                ], 400);
        }
    }

    private function handleFileUpload($request, $field, $directory)
    {
        // Check if a file is uploaded
        if ($request->hasFile($field)) {
            // Validate and store the new file
            $file = $request->file($field);
            $this->validateFile($file); // Ensure valid file

            // Store the file
            $filePath = $file->store($directory, 'public');

            // Log the file path for debugging
            Log::info('File stored at: ' . $filePath);

            return $filePath;
        }

        return null; // Return null if no new file uploaded
    }

    private function validateFile($file)
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $maxSize = 2048; // Max size in kilobytes

        if (!in_array($file->getMimeType(), $allowedMimeTypes) || $file->getSize() > ($maxSize * 1024)) {
            throw new \InvalidArgumentException('Invalid file type or size.');
        }
    }

    public function fetchMessages($conversationId)
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    private function formatMessageResponse($message)
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender_name,
            'message' => $message->message,
            'image_url' => $message->image ? Storage::url($message->image) : null,
            'created_at' => $message->created_at->toDateTimeString(),
        ];
    }
}
