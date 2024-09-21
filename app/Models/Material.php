<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, $task_id)
 * @method static find($material_id)
 */
class Material extends Model
{
    use HasFactory;

    protected $guarded = [];
}
