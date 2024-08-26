<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    private User $model;
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function checkOrCreate(string $chat_id): array
    {
        $user = $this->model->where('chat_id', $chat_id)->first();
        if ($user && $user->language !== null && $user->phone !== null && $user->status == 'active') {
            return [
                'exists' => true,
                'user' => $user
            ];
        }
        $user = $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'status' => 'active']);
        return [
            'exists' => false,
            'user' => $user
        ];
    }

    public function page($chat_id, $step=null)
    {
        return $step ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'step' => $step]) : $this->model::where('chat_id', $chat_id)->first()->step;
    }

    public function language($chat_id, $language=null)
    {
        return $language ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'language' => $language]) : $this->model::where('chat_id', $chat_id)->first()->language;
    }

    public function phone($chat_id, $phone=null) {
        return $phone ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'phone' => $phone]) : $this->model::where('chat_id', $chat_id)->first()->phone;
    }

    public function delete($chat_id): void
    {
        $this->model->where('chat_id', $chat_id)->update(['status' => 'delete-account']);
    }
}
