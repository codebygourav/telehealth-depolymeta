<?php

namespace App\Services;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class ApiResponseService
{
    /**
     * SUCCESS (200)
     */
    public static function success(
        ?string $responseKey = null,
        array $extra = [],
        mixed $data = null,
        ?string $code = null
    ): JsonResponse {
        $responseKey = $responseKey ?? 'responses.success';

        $message = __($responseKey);

        // Prepare base response structure
        $responseArr = [
            'success' => true,
            'message' => $message,
        ];
        
        if ($code !== null) {
            $responseArr['code'] = $code;
        }

        // Merge extra keys (metadata, pagination, etc.)
        if (!empty($extra)) {
            foreach ($extra as $key => $value) {
                if (!array_key_exists($key, $responseArr)) {
                    $responseArr[$key] = $value;
                }
            }
        }

        // Add path and timestamp
        $responseArr['path'] = request()->path();
        $responseArr['timestamp'] = now()->toIso8601String();

        // Add data (default to empty array if null)
        $responseArr['data'] = $data ?? [];

        return response()->json($responseArr, 200);
    }

    /**
     * CREATED (201)
     */
    public static function created(
        ?string $responseKey = null,
        mixed $data = null
    ): JsonResponse {
        $responseKey = $responseKey ?? 'responses.created';

        $message = __($responseKey);

        $response = new ApiResponse(
            status: true,
            message: $message,
            data: $data
        );

        return $response->toJsonResponse(201);
    }

    /**
     * VALIDATION ERROR (422)
     */
    public static function validationError(
        mixed $errors,
        ?string $message = null,
        ?string $code = 'VALIDATION_ERROR'
    ): JsonResponse {

        $errorMessage = 'Validation failed.';

        if (is_string($errors)) {
            $errorMessage = $errors;
        } elseif ($errors instanceof \Illuminate\Support\MessageBag) {
            $errorMessage = $errors->first();
        } elseif (is_array($errors)) {
            $errorMessage = collect($errors)->flatten()->first() ?? 'Validation failed.';
        }

        $errorsOutput = [
            'message' => $errorMessage
        ];

        return self::error(
            responseKey: 'responses.validation_failed',
            errors: $errorsOutput,
            statusCode: 422,
            message: $message,
            code: $code
        );
    }




    /**
     * UNAUTHENTICATED (401)
     */
    public static function unauthenticated(): JsonResponse
    {
        return self::error('responses.unauthenticated', null, 401);
    }

    /**
     * UNAUTHORIZED (403)
     */
    public static function unauthorized(): JsonResponse
    {
        return self::error('responses.forbidden', null, 403);
    }

    /**
     * NOT FOUND (404)
     */
    public static function notFound(?string $resource = null, ?string $module = null): JsonResponse
    {
        // Construct a module-specific response key if applicable.
        $responseKey = $module
            ? "responses.{$module}.not_found"
            : 'responses.not_found';

        // If a resource is specified, try translated fallback or append resource name.
        $message = $resource
            ? __(($responseKey), ['resource' => $resource])
            : __($responseKey);

        // If translation doesn't exist (returns fallback key), fallback to default with resource name.
        if ($message === $responseKey || $message === "responses.not_found") {
            $message = $resource
                ? __('responses.not_found', ['resource' => $resource])
                : __('responses.not_found');
        }

        return self::error($responseKey, null, 404);
    }

    /**
     * RATE LIMITED (429)
     */
    public static function rateLimited(): JsonResponse
    {
        return self::error('responses.rate_limit', null, 429);
    }

    /**
     * CONFLICT (409)
     */
    public static function conflict(): JsonResponse
    {
        return self::error('responses.conflict', null, 409);
    }

    /**
     * SERVICE UNAVAILABLE (503)
     */
    public static function serviceUnavailable(): JsonResponse
    {
        return self::error('responses.service_unavailable', null, 503);
    }

    /**
     * GATEWAY TIMEOUT (504)
     */
    public static function timeout(): JsonResponse
    {
        return self::error('responses.timeout', null, 504);
    }

    /**
     * SERVER ERROR (500)
     */
    public static function serverError(?Throwable $exception = null): JsonResponse
    {
        $errors = null;
        $trace = null;

        if (config('app.debug') && $exception) {
            $errors = ['exception' => $exception->getMessage()];
            $trace = $exception->getTraceAsString();
        }

        $response = new ApiResponse(
            status: false,
            message: __('responses.server_error'),
            errors: $errors,
            trace: $trace
        );

        return $response->toJsonResponse(500);
    }

    /**
     * GENERIC ERROR HANDLER
     */
    public static function error(
        string $responseKey,
        mixed $errors = null,
        int $statusCode = 400,
        mixed $message = null,
        ?string $code = null
    ): JsonResponse {
        $message = $message ?? __($responseKey);

        if ($errors === null) {
            $errors = ['message' => $message];
        } elseif (is_string($errors)) {
            $errors = ['message' => $errors];
        } elseif ($errors instanceof \Illuminate\Support\MessageBag) {
            $errors = ['message' => $errors->first()];
        } elseif (is_array($errors)) {
            if (!isset($errors['message'])) {
                $firstError = collect($errors)->flatten()->first();
                $errors = ['message' => $firstError ?? $message];
            } else {
                if (is_array($errors['message'])) {
                    $errors['message'] = collect($errors['message'])->flatten()->first() ?? $message;
                }
            }
        }

        $response = new ApiResponse(
            status: false,
            message: $message,
            errors: $errors,
            code: $code
        );

        return $response->toJsonResponse($statusCode);
    }

    /**
     * PAGINATED RESPONSE
     */
    public static function paginated($paginated, ?string $responseKey = null, array $extra = []): JsonResponse
    {
        $responseKey = $responseKey ?? 'responses.success';

        // Get items and ensure they are converted to arrays if they are resources
        $items = $paginated->getCollection();
        $formattedItems = [];
        foreach ($items as $item) {
            if (is_object($item) && method_exists($item, 'toArray')) {
                $formattedItems[] = $item->toArray(request());
            } else {
                $formattedItems[] = (array) $item;
            }
        }

        // Ensure 'data' is always an array for paginated collections
        $data = $formattedItems;
        if (empty($formattedItems)) {
            $data = [];
        }

        // Prepare pagination info
        $pagination = [
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ];

        // Merge custom extra data with pagination
        $finalExtra = array_merge(['pagination' => $pagination], $extra);

        return self::success(
            responseKey: $responseKey,
            data: $data,
            extra: $finalExtra
        );
    }
}