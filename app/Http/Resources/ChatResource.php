<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $theme
 * @property mixed $id
 * @property mixed $messages
 * @method messages()
 */
class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $message = $this->messages()->first();
        return [
            'id' => $this->id,
            'theme' => $this->theme,
            'message' => $message ? $message->message : null,
        ];
    }
}
