<?php

namespace App\Exceptions;

use App\Services\ApiResponseService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Global Exception Handler
 *
 * Handles all exceptions and converts them to API responses for JSON requests
 * and to HTML responses for web requests.
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into a response.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            $allMessages = collect($e->errors())->flatten()->values()->all();
            $firstMessage = $allMessages[0] ?? 'Validation failed.';

            return response()->json([
                'success' => false,
                'message' => $firstMessage,
                'errors' => [
                    'message' => count($allMessages) === 1 ? $firstMessage : $allMessages,
                ],
            ], 422);
        }

        // If the request expects JSON (API or AJAX), convert all exceptions
        // to our standard API response format so frontends receive consistent keys.
        if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
            return $this->renderJsonException($e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle all API exceptions
     *
     * All exception types are handled here in one place
     */
    protected function renderJsonException(Throwable $exception)
    {
        // 1. Validation Exception (422)
        if ($exception instanceof ValidationException) {
            return ApiResponseService::validationError($exception->errors());
        }

        // 2. Authentication Exception (401)
        if ($exception instanceof AuthenticationException) {
            return ApiResponseService::unauthenticated();
        }

        // 3. Authorization Exception (403)
        if ($exception instanceof AuthorizationException) {
            return ApiResponseService::unauthorized();
        }

        // 4. Custom API Exception
        if ($exception instanceof \App\Exceptions\Api\ApiException) {
            return $this->handleApiException($exception);
        }

        // 5. Model Not Found (404)
        if ($exception instanceof ModelNotFoundException) {
            return ApiResponseService::notFound();
        }

        // 6. Route Not Found (404)
        if ($exception instanceof NotFoundHttpException) {
            return ApiResponseService::notFound();
        }

        // 7. Access Denied (403)
        if ($exception instanceof AccessDeniedHttpException) {
            return ApiResponseService::unauthorized();
        }

        // 8. Rate Limiting (429)
        if ($exception instanceof ThrottleRequestsException) {
            return ApiResponseService::rateLimited();
        }

        // 9. HTTP Exceptions (4xx/5xx)
        if ($exception instanceof HttpException) {
            return $this->handleHttpException($exception);
        }

        // 10. Default Server Error (500)
        return ApiResponseService::serverError($exception);
    }

    /**
     * Handle Custom API Exception
     */
    protected function handleApiException(\App\Exceptions\Api\ApiException $exception)
    {
        $responseKey = $exception->getResponseKey();
        $httpCode = $exception->getHttpCode();
        $errors = $exception->getErrors();
        $module = $exception->getModule();

        // Optionally include module information inside errors, but always use the HTTP code
        if ($module) {
            $errors['module'] = $module;
        }

        return ApiResponseService::error($responseKey, $errors, $httpCode);
    }

    /**
     * Handle HTTP Exceptions by status code
     */
    protected function handleHttpException(HttpException $exception)
    {
        $statusCode = $exception->getStatusCode();

        return match ($statusCode) {
            400 => ApiResponseService::error('common.validation_failed'),
            401 => ApiResponseService::unauthenticated(),
            403 => ApiResponseService::unauthorized(),
            404 => ApiResponseService::notFound(),
            409 => ApiResponseService::conflict(),
            429 => ApiResponseService::rateLimited(),
            503 => ApiResponseService::serviceUnavailable(),
            504 => ApiResponseService::timeout(),
            default => ApiResponseService::serverError(null, $exception),
        };
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return ApiResponseService::unauthenticated();
        }

        return parent::unauthenticated($request, $exception);
    }

    /**
     * Prepare exception for rendering.
     */
    public function prepareException(Throwable $e)
    {
        return parent::prepareException($e);
    }
}
