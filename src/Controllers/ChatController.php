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
            $messages = Message::with('media', 'userProfile.profilePic.media', 'userProfile:id,f_name,l_name')->where('conversation_id', $request->conversation_id)->get();
            return $messages;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public static function createGroup($request)
    {
        try {
            $conversation = Conversation::create($request);
            if (!empty($request['participants'])) {
                foreach ($request['participants'] as $participant) {
                    $participant['conversation_id'] = $conversation->id;
                    Participant::create($participant);
                }
            }
            return $conversation;
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
            $conversationIds = Participant::where('user_id', $request['user_id'])->pluck('conversation_id')->toArray();
            $participants = Participant::with('userProfile.profilePic.media', 'userProfile:id,f_name,l_name')->whereIn('conversation_id', $conversationIds)->where('user_id', '!=', $request['user_id'])->get();
            return $participants;
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
            $message = $message->load('media', 'userProfile:id,f_name,l_name', 'userProfile.profilePic.media');
            event(new ChatEvent($request['receiver_id'], $message));
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
