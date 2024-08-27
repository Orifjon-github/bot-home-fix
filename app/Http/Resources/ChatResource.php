<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $theme
 * @property mixed $id
 * @property mixed $messages
 * @property mixed $title
 * @property mixed $message
 * @method messages()
 */
class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'theme' => $this->theme,
            'title' => $this->title,
            'message' => $this->message,
            'files' => 'soon'
        ];
    }
}
