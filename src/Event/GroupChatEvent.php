<?php

namespace Semantechs\Chat\Event;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $groupId, $senderId, $body;

    /**
     * Create a new event instance.
     */
    public function __construct($groupId, $senderId, $body)
    {
        $this->groupId = $groupId;
        $this->senderId = $senderId;
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
            env('APP_NAME') . '.' . $this->groupId
        ];
    }

    public function broadcastAs()
    {
        return env('APP_NAME') . '.' . $this->groupId;
    }
}
