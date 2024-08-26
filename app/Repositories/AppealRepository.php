<?php

namespace App\Repositories;

use App\Models\AppealType;
use App\Models\Chat;
use App\Models\User;

class AppealRepository
{
    private Chat $model;
    private AppealType $appealModel;
    public function __construct(Chat $model, AppealType $appealModel)
    {
        $this->model = $model;
        $this->appealModel = $appealModel;
    }

    public function getAppealType($attr, $value)
    {
        return $this->appealModel->where($attr, $value)->where('enable', 1)->first() ?? null;
    }

    public function updateOrCreateAppeal($chat_id, array $data)
    {
        $user = User::where('chat_id', $chat_id)->first();
        $chat = $user->chats()->where('status', 'create')->first();

        if ($chat) {
            return $chat->update($data);
        } else {
            return $user->chats()->create($data);
        }
    }
}
