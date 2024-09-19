<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, $object_id)
 * @method static find($branch_id)
 */
class Branch extends Model
{
    use HasFactory;

    protected $guarded = [];
}
