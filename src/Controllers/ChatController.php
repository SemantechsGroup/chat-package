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
    public static function getUserChat(Request $request)
    {
        try {
            $userChat = Chat::with('sender_id', 'receiver_id')->where('sender_id', $request->sender_id)->where('receiver_id', $request->receiver_id)->latest()->get();
            return $userChat;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function getGroupChat(Request $request)
    {
        try {
            $groupMembers = GroupMember::where('group_id', $request->group_id)->pluck('user_id')->toArray();
            if (!in_array($request->user_id, $groupMembers)) {
                return response('Un-Authorized', 401);
            } else {
                $groupChat = GroupChat::where('group_id')->latest()->get();
                return $groupChat;
            }
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function sendChatMessage(Request $request)
    {
        try {
            $data = $request->all();
            event(new UserChatEvent($data['sender_id'], $data['receiver_id'], $data['body']));
            $data['body'] = json_encode($data['body']);
            Chat::create($data);
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function sendGroupMessage(Request $request)
    {
        try {
            $data = $request->all();
            event(new GroupChatEvent($data['group_id'], $data['sender_id'], $data['body']));
            $data['body'] = json_encode($data['body']);
            GroupChat::create($data['body']);
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function createChatGroup(Request $request)
    {
        try {
            $data = $request->all();
            $group = ChatGroup::create($data);
            return $group;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }
}
