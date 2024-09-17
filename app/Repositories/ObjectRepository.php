<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\Objects;
use App\Models\User;

class ObjectRepository
{
    private Objects $model;
    private Branch $branch;
    public function __construct(Objects $model, Branch $branch)
    {
        $this->model = $model;
        $this->branch = $branch;
    }

    public function getLatestObject($chat_id)
    {
        $user = User::where('chat_id', $chat_id)->first();
        return $user->objects()->latest()->first();
    }

    public function createObject($chat_id, $name): bool
    {
        $user = User::where('chat_id', $chat_id)->first();
        $user->objects()->create(['name' => $name]);
        return true;
    }
    public function createBranch($chat_id, $name): bool
    {
        $user = User::where('chat_id', $chat_id)->first();
        $object = $user->objects()
        ->latest()
        ->first();
        $object->branches()->create(['name' => $name]);
        return true;
    }

    public function updateBranch($chat_id, $address): bool
    {
        $user = User::where('chat_id', $chat_id)->first();
        $object = $user->objects()
            ->latest()
            ->first();
        $object->branches()->update(['address' => $address]);
        return true;
    }

    public function deleteObject($chat_id): bool
    {
        $user = User::where('chat_id', $chat_id)->first();
        $user->objects()
            ->latest()
            ->first()
            ->delete();
        return true;
    }
}
