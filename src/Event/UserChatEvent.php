<?php

namespace Semantechs\Chat\Event;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $senderId, $receiverId, $body;

    /**
     * Create a new event instance.
     */
    public function __construct($senderId, $receiverId, $body)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->body = $body;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            env('APP_NAME') . '.' . $this->receiverId
        ];
    }

    public function broadcastAs()
    {
        return env('APP_NAME') . '.' . $this->receiverId;
    }
}
