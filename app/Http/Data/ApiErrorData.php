<?php

namespace App\Http\Data;

use Illuminate\Http\JsonResponse;

readonly class ApiErrorData
{
    public function __construct(
        public string $message,
    ) {}

    /**
     * @return array{message: string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }

    public function toJsonResponse(int $status): JsonResponse
    {
        return response()->json($this->toArray(), $status);
    }
}
