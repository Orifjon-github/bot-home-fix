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

    public function createAppeal($chat_id, array $data): AppealType
    {
        $user = User::where('chat_id', $chat_id)->first();
        return $user->chats()->updateOrCreate(['user_id' => $user->id], $data);
    }
}
