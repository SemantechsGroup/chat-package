<?php

namespace Semantechs\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id', 'receiver_id', 'body'
    ];

    public function sender()
    {
        return $this->hasOne(App\Models\UserProfile::class, 'id', 'sender_id');
    }

    public function receiver()
    {
        return $this->hasOne(App\Models\UserProfile::class, 'id', 'receiver_id');
    }
}
