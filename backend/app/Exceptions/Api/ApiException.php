<?php

namespace App\Exceptions\Api;

use Exception;

/**
 * Global API Exception
 *
 * Single exception class for all API errors.
 * The Handler.php will catch and convert to appropriate responses.
 */
class ApiException extends Exception
{
    public function __construct(
        private string $responseKey = 'common.server_error',
        private int $httpCode = 500,
        private ?array $errors = null,
        private ?string $module = null,
        string $message = '',
        Exception $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
    }

    public function getResponseKey(): string
    {
        return $this->responseKey;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }
}
