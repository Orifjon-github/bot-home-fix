<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static where(string $string, mixed $text)
 * @method find(mixed $task_id)
 */
class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
    public function images(): HasMany
    {
        return $this->hasMany(TaskImage::class);
    }
}
