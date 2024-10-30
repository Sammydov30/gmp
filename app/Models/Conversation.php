<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Message;


class Conversation extends Model
{
    protected $fillable = [
        'participant_id',
        'representative_id',
        'status',  // 'open', 'closed'
    ];

    // Relationship: A conversation has many messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }


    // Relationship: A conversation belongs to a representative
    public function representative()
    {
        return $this->belongsTo(User::class, 'representative_id');
    }
}
