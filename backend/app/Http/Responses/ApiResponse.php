<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public function __construct(
        public bool $status,
        public string $message,
        public mixed $data = null,
        public mixed $errors = null,
        public ?string $path = null,
        public ?string $trace = null,
        public ?string $code = null
    ) {
        $this->path = $path ?? request()->path();
    }

    public function toJsonResponse(int $statusCode): JsonResponse
    {
        $response = [
            'success' => $this->status,
            'message' => $this->message,
            'path' => $this->path,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->data ?? [],
        ];

        if ($this->code !== null) {
            $response['code'] = $this->code;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        // Show trace only when APP_DEBUG=true
        if ($this->trace !== null && config('app.debug')) {
            $response['trace'] = $this->trace;
        }

        return response()->json($response, $statusCode);
    }
}
