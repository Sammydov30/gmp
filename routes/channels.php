<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{sessionId}', function ($user, $sessionId) {
    return true;
});
