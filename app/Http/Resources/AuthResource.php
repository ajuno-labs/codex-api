<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    protected $token;
    protected $message;

    public function __construct($resource, $token = null, $message = null)
    {
        parent::__construct($resource);
        $this->token = $token;
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => $this->message ?? 'Authentication successful',
            'user' => new UserResource($this->resource),
            'token' => $this->token,
        ];
    }
}
