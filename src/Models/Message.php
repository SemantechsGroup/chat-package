<?php

namespace Semantechs\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'conversation_id', 'text', 'is_read', 'media_id'
    ];

    public function userProfile()
    {
        return $this->hasOne(\App\Models\UserProfile::class, 'id', 'user_id');
    }

    public function media()
    {
        return $this->hasOne(\App\Models\MediaFile::class, 'id', 'media_id');
    }
}
