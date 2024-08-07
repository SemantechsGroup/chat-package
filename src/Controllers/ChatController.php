<?php

namespace Semantechs\Chat\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Semantechs\Chat\Event\ChatEvent;
use Semantechs\Chat\Models\Conversation;
use Semantechs\Chat\Models\Message;
use Semantechs\Chat\Models\Participant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public static function getMessages($request)
    {
        try {
            $messages = Message::with('media', 'userProfile.profilePic')->where('conversation_id', $request->conversation_id)->get();
            return $messages;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function createGroup($request)
    {
        try {
            $conversation = Conversation::create($request);
            Participant::create(['user_id' => $request['user_id'], 'conversation_id' => $conversation->id]);
            if (!empty($request['participants'])) {
                foreach ($request['participants'] as $participant) {
                    $temp = [
                        'user_id' => $participant,
                        'conversation_id' => $conversation->id,
                    ];
                    Participant::create($temp);
                }
            }
            return $conversation;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function updateGroup($request)
    {
        try {
            $conversation = Conversation::find($request['conversation_id']);
            $conversation->fill(['name' => $request['name']])->save();
            $conParticipantsArray = Participant::where('conversation_id', $request['conversation_id'])->pluck('user_id')->toArray();
            $reqParticipantsArray = $request['participants'];

            $dbArray = array_diff($conParticipantsArray, $reqParticipantsArray);
            foreach ($dbArray as $item) {
                $participant = Participant::where('user_id', $item)->where('conversation_id', $request['conversation_id'])->first();
                $participant->delete();
            }

            $reqArray = array_diff($reqParticipantsArray, $conParticipantsArray);
            foreach ($reqArray as $item) {
                $temp = [
                    'user_id' => $item,
                    'conversation_id' => $request['conversation_id']
                ];
                Participant::create($temp);
            }

            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function addParticipants($request)
    {
        try {
            if (!empty($request['participants'])) {
                foreach ($request['participants'] as $participant) {
                    Participant::create($participant);
                }
            }
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function getParticipants($request)
    {
        try {
            $participants = [];
            $conversationIds = Participant::where('user_id', $request['user_id'])->pluck('conversation_id')->toArray();
            foreach ($conversationIds as $conversationId) {
                $msgCount = 0;
                $participant = Participant::where('user_id', $request['user_id'])->where('conversation_id', $conversationId)->whereNotNull('last_read_at')->first();
                if ($participant) {
                    $msgCount = Message::where('user_id', '!=', $request['user_id'])->where('conversation_id', $conversationId)->where('created_at', '>', $participant->last_read_at)->count();
                }
                $group = Conversation::whereNotNull('name')->find($conversationId);
                if ($group) {
                    $group['msg_count'] = $msgCount;
                    array_push($participants, $group);
                }
            }
            $dbParticipants = Participant::with('userProfile.profilePic', 'conversation')->whereIn('conversation_id', $conversationIds)->where('user_id', '!=', $request['user_id'])->whereHas('conversation', function ($query) {
                $query->whereNull('name');
            })->get();
            foreach ($dbParticipants as $dbParticipant) {
                $msgCount = Message::whereIn('conversation_id', $conversationIds)->where('user_id', $dbParticipant['user_id'])->where('is_read', 0)->count();
                $dbParticipant['msg_count'] = $msgCount;
                array_push($participants, $dbParticipant);
            }
            return $participants;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function getListOfGroups($request)
    {
        try {
            $groups = Conversation::where('user_id', $request['user_id'])->where('name', '!=', '')->get();
            return $groups;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function getSingleGroup($request)
    {
        try {
            $group = Conversation::with('participants')->find($request['conversation_id']);
            return $group;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function getGroups($request)
    {
        try {
            $conversationIds = Participant::where('user_id', $request['user_id'])->pluck('conversation_id')->toArray();
            $groups = Conversation::whereIn('id', $conversationIds)->get();
            return $groups;
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
            if (!empty($request['attachment'])) {
                $media = self::uploadMedia($request['attachment'], $request['conversation_id'], $request['attachment_type']);
                $request['media_id'] = $media->id;
            }
            $message = Message::create($request);
            $message = $message->load('media', 'userProfile.profilePic');
            if (!isset($request['receiver_id'])) {
                $participants = Participant::where('conversation_id', $request['conversation_id'])->where('user_id', '!=', $request['user_id'])->pluck('user_id')->toArray();
                foreach ($participants as $user) {
                    event(new ChatEvent($user, $message));
                }
            } else {
                event(new ChatEvent($request['receiver_id'], $message));
            }
            return $message;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function deleteGroup($request)
    {
        try {
            $conversation = Conversation::find($request['conversation_id']);
            $conversation->delete();
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function readChatMessages($request)
    {
        try {
            Participant::where('conversation_id', $request['conversation_id'])->where('user_id', $request['user_id'])->update(['last_read_at' => Carbon::now()]);
            Message::where('conversation_id', $request['conversation_id'])->where('user_id', '!=', $request['user_id'])->where('is_read', 0)->update(['is_read' => 1]);
            return 'success';
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    private static function createFolder($folderId)
    {
        $folderName = 'public/' . $folderId;
        $target = app()->basePath('storage/app/' . $folderName);
        if (!file_exists(app()->basePath('public/storage'))) {
            File::makeDirectory(app()->basePath('public/storage'), 0755, true);
        }

        if (!File::isDirectory($target)) {
            File::makeDirectory($target, 0755, true);
        }

        $link = app()->basePath('public/storage/' . $folderId);
        if (!file_exists($link)) {
            File::link($target, $link);
        }

        return $folderName;
    }

    private static function uploadMedia($base64Image, $path, $type)
    {
        $folderName = self::createFolder($path);
        $extension = explode('/', mime_content_type($base64Image));
        $image = str_replace("data:image/{$extension[1]};base64,", '', $base64Image);
        $image = str_replace(' ', '+', $image);
        $uniqueIdentifier = uniqid();
        $newImageName = Carbon::now()->timestamp . $uniqueIdentifier . '.' . $extension[1];
        $storagePath = $folderName . '/' . $newImageName;
        Storage::put($storagePath, base64_decode($image));
        return \App\Models\MediaFile::create([
            'path' => $folderName,
            'filename' => $newImageName,
            'type' => $type,
        ]);
    }
}
