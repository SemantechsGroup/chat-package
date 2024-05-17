<?php

namespace Semantechs\Chat\Event;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $receiverId, $userId, $conversationId, $text;

    /**
     * Create a new event instance.
     */
    public function __construct($receiverId, $userId, $conversationId, $text)
    {
        $this->receiverId = $receiverId;
        $this->userId = $userId;
        $this->conversationId = $conversationId;
        $this->text = $text;
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
