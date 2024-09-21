<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\Material;
use App\Models\Objects;
use App\Models\Task;
use App\Models\User;

class ObjectRepository
{
    private Objects $model;
    private Branch $branch;
    private Task $task;
    private Material $material;
    public function __construct(Objects $model, Branch $branch, Task $task, Material $material)
    {
        $this->model = $model;
        $this->branch = $branch;
        $this->task = $task;
        $this->material = $material;
    }

    public function getLatestObject($chat_id)
    {
        $user = User::where('chat_id', $chat_id)->first();
        return $user->objects()->latest()->first();
    }

    public function getLatestBranch($object_id)
    {
        $object = $this->model->find($object_id);
        return $object->branches()->latest()->first();
    }

    public function createObject($chat_id, $name): bool
    {
        $user = User::where('chat_id', $chat_id)->first();
        $user->objects()->create(['name' => $name]);
        return true;
    }
    public function createBranch($chat_id, $name, $object_id=null): bool
    {
        $user = User::where('chat_id', $chat_id)->first();
        if ($object_id) {
            $object = $this->model->find($object_id);
        } else {
            $object = $user->objects()
                ->latest()
                ->first();
        }

        $object->branches()->create(['name' => $name]);
        return true;
    }

    public function createTask($chat_id, $name, $branch_id=null)
    {
        $user = User::where('chat_id', $chat_id)->first();
        if ($branch_id) {
            $branch = $this->branch->find($branch_id);
        } else {
            $branch = $user->objects()
                ->latest()
                ->first()
                ->branches()
                ->latest()
                ->first();
        }

        return $branch->tasks()->create(['name' => $name]);
    }

    public function createMaterial($chat_id, $name, $task_id=null)
    {
        $user = User::where('chat_id', $chat_id)->first();
        if ($task_id) {
            $task = $this->task->find($task_id);
        } else {
            $task = $user->objects()
                ->latest()
                ->first()
                ->branches()
                ->latest()
                ->first()
                ->tasks()
                ->latest()
                ->first();
        }

        return $task->materials()->create(['name' => $name]);
    }

    public function updateBranch($chat_id, $address, $object_id=null): bool
    {
        $user = User::where('chat_id', $chat_id)->first();
        if ($object_id) {
            $object = $this->model->find($object_id);
        } else {
            $object = $user->objects()
                ->latest()
                ->first();
        }
        $object->branches()->update(['address' => $address]);
        return true;
    }

    public function updateTask($data, $task_id): bool
    {
        $task = $this->task->find($task_id);
        $task->update($data);
        return true;
    }

    public function updateMaterial($data, $material_id): bool
    {
        $task = $this->material->find($material_id);
        $task->update($data);
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
