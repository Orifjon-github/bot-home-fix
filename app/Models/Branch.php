<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static where(string $string, $object_id)
 * @method static find($branch_id)
 */
class Branch extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(Objects::class, 'objects_id');
    }
}
