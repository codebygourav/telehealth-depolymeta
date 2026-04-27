<?php

/**
 * Global API Response Configuration
 *
 * This config file defines all API response messages and error codes
 * for consistent, structured API responses across the entire application.
 *
 * Structure:
 * - common: Shared responses across all modules (auth, validation, etc.)
 * - modules: Module-specific responses with their own message keys
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Common Responses (Global)
    |--------------------------------------------------------------------------
    | These responses are used across all API endpoints and are not tied
    | to any specific module.
    */

    'common' => [
        // Success Responses
        'success' => [
            'code' => 200,
            'message' => 'responses.success',
            'description' => 'Operation completed successfully',
        ],
        'created' => [
            'code' => 201,
            'message' => 'responses.created',
            'description' => 'Resource created successfully',
        ],
        'updated' => [
            'code' => 200,
            'message' => 'responses.updated',
            'description' => 'Resource updated successfully',
        ],
        'deleted' => [
            'code' => 200,
            'message' => 'responses.deleted',
            'description' => 'Resource deleted successfully',
        ],

        // Client Errors (4xx)
        'validation_failed' => [
            'code' => 422,
            'message' => 'responses.validation_failed',
            'description' => 'Validation failed. Please check your input.',
        ],
        'unauthenticated' => [
            'code' => 401,
            'message' => 'responses.unauthenticated',
            'description' => 'User is not authenticated. Please login.',
        ],
        'unauthorized' => [
            'code' => 403,
            'message' => 'responses.forbidden',
            'description' => 'User is not authorized to perform this action.',
        ],
        'forbidden' => [
            'code' => 403,
            'message' => 'responses.forbidden',
            'description' => 'Access denied. Insufficient permissions.',
        ],
        'not_found' => [
            'code' => 404,
            'message' => 'responses.not_found',
            'description' => 'Requested resource not found.',
        ],
        'conflict' => [
            'code' => 409,
            'message' => 'responses.conflict',
            'description' => 'Request conflicts with existing resource.',
        ],
        'gone' => [
            'code' => 410,
            'message' => 'responses.gone',
            'description' => 'Requested resource is no longer available.',
        ],
        'rate_limit' => [
            'code' => 429,
            'message' => 'responses.rate_limit',
            'description' => 'Too many requests. Please try again later.',
        ],

        // Server Errors (5xx)
        'server_error' => [
            'code' => 500,
            'message' => 'responses.error',
            'description' => 'An internal server error occurred.',
        ],
        'service_unavailable' => [
            'code' => 503,
            'message' => 'responses.service_unavailable',
            'description' => 'Service is temporarily unavailable.',
        ],
        'timeout' => [
            'code' => 504,
            'message' => 'responses.timeout',
            'description' => 'Request timeout. Please try again.',
        ],

        // Custom Business Errors
        'invalid_credentials' => [
            'code' => 401,
            'message' => 'responses.invalid_credentials',
            'description' => 'Invalid credentials provided.',
        ],
        'invalid_token' => [
            'code' => 401,
            'message' => 'responses.invalid_token',
            'description' => 'Invalid or expired token.',
        ],
        'token_expired' => [
            'code' => 401,
            'message' => 'responses.token_expired',
            'description' => 'Token has expired. Please login again.',
        ],
        'duplicate_entry' => [
            'code' => 409,
            'message' => 'responses.duplicate_entry',
            'description' => 'This resource already exists.',
        ],
        'operation_failed' => [
            'code' => 422,
            'message' => 'responses.operation_failed',
            'description' => 'Operation could not be completed.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module-Specific Responses
    |--------------------------------------------------------------------------
    | Define custom responses for specific modules. These override common
    | responses when a module-specific key is provided.
    |
    | Format: 'module_name' => ['response_key' => ['code' => ..., 'message' => ...]]
    */

    'modules' => [
        // Patient Module
        'patient' => [
            'created' => [
                'code' => 201,
                'message' => 'responses.patient.created',
                'description' => 'Patient registered successfully',
            ],
            'updated' => [
                'code' => 200,
                'message' => 'responses.patient.updated',
                'description' => 'Patient information updated successfully',
            ],
            'deleted' => [
                'code' => 200,
                'message' => 'responses.patient.deleted',
                'description' => 'Patient record deleted successfully',
            ],
            'not_found' => [
                'code' => 404,
                'message' => 'responses.patient.not_found',
                'description' => 'Patient record not found',
            ],
            'invalid_data' => [
                'code' => 422,
                'message' => 'responses.patient.invalid_data',
                'description' => 'Invalid patient data provided',
            ],
        ],

        // Doctor Module
        'doctor' => [
            'created' => [
                'code' => 201,
                'message' => 'responses.doctor.created',
                'description' => 'Doctor profile created successfully',
            ],
            'updated' => [
                'code' => 200,
                'message' => 'responses.doctor.updated',
                'description' => 'Doctor profile updated successfully',
            ],
            'deleted' => [
                'code' => 200,
                'message' => 'responses.doctor.deleted',
                'description' => 'Doctor profile deleted successfully',
            ],
            'not_found' => [
                'code' => 404,
                'message' => 'responses.doctor.not_found',
                'description' => 'Doctor not found',
            ],
            'unavailable' => [
                'code' => 422,
                'message' => 'responses.doctor.unavailable',
                'description' => 'Doctor is not available at the moment',
            ],
        ],

        // Appointment Module
        'appointment' => [
            'created' => [
                'code' => 201,
                'message' => 'responses.appointment.created',
                'description' => 'Appointment booked successfully',
            ],
            'updated' => [
                'code' => 200,
                'message' => 'responses.appointment.updated',
                'description' => 'Appointment updated successfully',
            ],
            'cancelled' => [
                'code' => 200,
                'message' => 'responses.appointment.cancelled',
                'description' => 'Appointment cancelled successfully',
            ],
            'not_found' => [
                'code' => 404,
                'message' => 'responses.appointment.not_found',
                'description' => 'Appointment not found',
            ],
            'conflict' => [
                'code' => 409,
                'message' => 'responses.appointment.conflict',
                'description' => 'Time slot is already booked',
            ],
            'expired' => [
                'code' => 422,
                'message' => 'responses.appointment.expired',
                'description' => 'Cannot modify past appointments',
            ],
        ],

        // Authentication Module
        'auth' => [
            'login_success' => [
                'code' => 200,
                'message' => 'responses.auth.login_success',
                'description' => 'Login successful',
            ],
            'logout_success' => [
                'code' => 200,
                'message' => 'responses.auth.logout_success',
                'description' => 'Logout successful',
            ],
            'registered' => [
                'code' => 201,
                'message' => 'responses.auth.registered',
                'description' => 'User registered successfully',
            ],
            'email_verified' => [
                'code' => 200,
                'message' => 'responses.auth.email_verified',
                'description' => 'Email verified successfully',
            ],
            'password_reset' => [
                'code' => 200,
                'message' => 'responses.auth.password_reset',
                'description' => 'Password reset successfully',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Code Mapping
    |--------------------------------------------------------------------------
    | Map exception types to API response codes and messages.
    | This allows automatic error handling based on exception type.
    */

    'error_codes' => [
        'ModelNotFoundException' => 'not_found',
        'ValidationException' => 'validation_failed',
        'AuthenticationException' => 'unauthenticated',
        'AuthorizationException' => 'unauthorized',
        'AccessDeniedHttpException' => 'forbidden',
        'NotFoundHttpException' => 'not_found',
        'MethodNotAllowedHttpException' => 'not_found',
        'RouteNotFoundException' => 'not_found',
        'ThrottleException' => 'rate_limit',
        'TimeoutException' => 'timeout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Settings
    |--------------------------------------------------------------------------
    */

    'debug' => env('API_DEBUG', env('APP_DEBUG', false)),
    'show_stack_trace' => env('API_SHOW_STACK_TRACE', false),
    'log_errors' => env('API_LOG_ERRORS', true),
];
