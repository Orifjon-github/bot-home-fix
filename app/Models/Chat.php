<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static where(string $string, string $string1)
 * @method static find($id)
 */
class Chat extends Model
{
    use HasFactory;

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
