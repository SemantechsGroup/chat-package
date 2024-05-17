<?php

namespace Semantechs\Chat\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Semantechs\Chat\Event\ChatEvent;
use Semantechs\Chat\Models\Conversation;
use Semantechs\Chat\Models\Message;
use Semantechs\Chat\Models\Participant;

class ChatController extends Controller
{
    public static function getMessages($request)
    {
        try {
            $messages = Message::where('conversation_id', $request->conversation_id)->latest()->get();
            return $messages;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function getParticipants($request)
    {
        try {
            $messages = Message::where('conversation_id', $request->conversation_id)->latest()->get();
            return $messages;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function sendMessage($request)
    {
        try {
            if (empty($request['conversation_id'])) {
                $conversation = Conversation::create(['user_id' => $request['user_id']]);
                Participant::create(['user_id' => $request['user_id'], 'conversation_id' => $conversation->id]);
                Participant::create(['user_id' => $request['receiver_id'], 'conversation_id' => $conversation->id]);
                $request['conversation_id'] = $conversation->id;
            }
            event(new ChatEvent($request['receiver_id'], $request['user_id'], $conversation->id, $request['text']));
            Message::create($request);
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }
}
