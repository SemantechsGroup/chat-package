<?php

namespace Semantechs\Chat\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Semantechs\Chat\Event\GroupChatEvent;
use Semantechs\Chat\Event\UserChatEvent;
use Semantechs\Chat\Models\Chat;
use Semantechs\Chat\Models\ChatGroup;
use Semantechs\Chat\Models\GroupChat;
use Semantechs\Chat\Models\GroupMember;

class ChatController extends Controller
{
    public static function getUserChat($request)
    {
        try {
            $userChat = Chat::with('sender_id', 'receiver_id')->where('sender_id', $request['sender_id'])->where('receiver_id', $request['receiver_id'])->latest()->get();
            return $userChat;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function getGroupChat($request)
    {
        try {
            $groupMembers = GroupMember::where('group_id', $request['group_id'])->pluck('user_id')->toArray();
            if (!in_array($request['user_id'], $groupMembers)) {
                return response('Un-Authorized', 401);
            } else {
                $groupChat = GroupChat::where('group_id')->latest()->get();
                return $groupChat;
            }
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function sendChatMessage($request)
    {
        try {
            event(new UserChatEvent($request['sender_id'], $request['receiver_id'], $request['body']));
            $request['body'] = json_encode($request['body']);
            Chat::create($request);
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function sendGroupMessage($request)
    {
        try {
            event(new GroupChatEvent($request['group_id'], $request['sender_id'], $request['body']));
            $request['body'] = json_encode($request['body']);
            GroupChat::create($request);
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function createChatGroup($request)
    {
        try {
            $group = ChatGroup::create($request);
            return $group;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }
}
