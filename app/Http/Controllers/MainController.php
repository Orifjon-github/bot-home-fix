<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Http\Resources\ChatDetailResource;
use App\Http\Resources\ChatResource;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MainController extends Controller
{
    use Response;

    public function ping(Request $request): JsonResponse
    {
        return $this->success(['ip' => $request->ip()]);
    }
    public function openChats(Request $request): JsonResponse
    {
        $openChats = Chat::where('status', 'ready')->get();
        return $this->success(ChatResource::collection($openChats));
    }

    public function chatDetail($id, Request $request): JsonResponse
    {
        $adminID = $request->input('admin_id');
        $chat = Chat::find($id);
        if (!$chat) return $this->error('Chat not found');

        if ($chat->status != 'ready') {
            if (!$adminID || $chat->admin_id != $adminID) return $this->error('You cannot see another employee\'s active chat');
        };
        return $this->success(ChatDetailResource::make($chat));
    }

    public function adminChats(Request $request): JsonResponse
    {
        $adminID = $request->input('admin_id');
        if (!$adminID) return $this->error('admin_id is required');

        $chatStatus = $request->input('status') ?? 'active';

        $adminChats = Chat::where('admin_id', $adminID)->where('status', $chatStatus)->get();
        return $this->success(ChatResource::collection($adminChats));
    }
}
