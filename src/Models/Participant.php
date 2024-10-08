<?php

namespace Semantechs\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'conversation_id', 'last_read_at'
    ];

    public function userProfile()
    {
        return $this->hasOne(\App\Models\UserProfile::class, 'user_id', 'user_id');
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class, 'id', 'conversation_id');
    }
}
