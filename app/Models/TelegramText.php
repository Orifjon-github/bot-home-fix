<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method firstOrCreate(string[] $array, string[] $array1)
 * @method where($language, $text)
 */
class TelegramText extends Model
{
    use HasFactory;

    protected $guarded = [];
}
