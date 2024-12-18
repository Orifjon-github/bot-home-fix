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

    public function role($chat_id) {
        return $this->model::where('chat_id', $chat_id)->first()->role;
    }

    public function object($chat_id, $object_id=null, $set_null = false) {
        if ($set_null) {
            return $this->model->where('chat_id', $chat_id)->update(['object_id' => null]);
        }
        return $object_id ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'object_id' => $object_id]) : $this->model::where('chat_id', $chat_id)->first()->object_id;
    }

    public function branch($chat_id, $branch_id=null, $set_null = false) {
        if ($set_null) {
            return $this->model->where('chat_id', $chat_id)->update(['branch_id' => null]);
        }
        return $branch_id ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'branch_id' => $branch_id]) : $this->model::where('chat_id', $chat_id)->first()->branch_id;
    }

    public function task($chat_id, $task_id=null, $set_null = false) {
        if ($set_null) {
            return $this->model->where('chat_id', $chat_id)->update(['task_id' => null]);
        }
        return $task_id ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'task_id' => $task_id]) : $this->model::where('chat_id', $chat_id)->first()->task_id;
    }

    public function material($chat_id, $material_id=null, $set_null = false) {
        if ($set_null) {
            return $this->model->where('chat_id', $chat_id)->update(['material_id' => null]);
        }
        return $material_id ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'material_id' => $material_id]) : $this->model::where('chat_id', $chat_id)->first()->material_id;
    }

    public function delete($chat_id): void
    {
        $this->model->where('chat_id', $chat_id)->update(['status' => 'delete-account']);
    }

    public function name($chat_id, $name=null) {
        return $name ? $this->model->updateOrCreate(['chat_id' => $chat_id], ['chat_id' => $chat_id, 'name' => $name]) : $this->model::where('chat_id', $chat_id)->first()->name;
    }
}
