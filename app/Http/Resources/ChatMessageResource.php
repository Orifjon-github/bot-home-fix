<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $created_at
 * @property mixed $owner
 * @property mixed $message
 * @property mixed $id
 */
class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'owner' => $this->owner,
            'created_at' => $this->created_at
        ];
    }
}
