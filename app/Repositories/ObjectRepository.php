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

    public function createObject($chat_id, $name)
    {
        $user = User::where('chat_id', $chat_id)->first();
        return $user->objects()->create(['name' => $name]);
    }
    public function createBranch($name, $object_id)
    {
        $object = $this->model->find($object_id);
        return $object->branches()->create(['name' => $name]);
    }

    public function createTask($name, $branch_id)
    {
        $branch = $this->branch->find($branch_id);

        return $branch->tasks()->create(['name' => $name]);
    }

    public function createMaterial($name, $task_id)
    {
        $task = $this->task->find($task_id);
        return $task->materials()->create(['name' => $name]);
    }

    public function updateBranch($address, $branch_id): bool
    {
        $branch = $this->branch->find($branch_id);
        $branch->update(['address' => $address]);
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
}
