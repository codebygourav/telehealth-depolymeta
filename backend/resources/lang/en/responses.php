<?php

/**
 * API Response Messages (English)
 *
 * Centralized response messages for the entire API.
 * Access using: __('responses.key') or __('responses.module.key')
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Common Success Messages
    |--------------------------------------------------------------------------
    */
    'success' => 'Operation completed successfully.',
    'created' => 'Resource created successfully.',
    'updated' => 'Resource updated successfully.',
    'deleted' => 'Resource deleted successfully.',

    /*
    |--------------------------------------------------------------------------
    | Validation & Input Errors
    |--------------------------------------------------------------------------
    */
    'validation_failed' => 'Validation failed. Please check your input.',
    'invalid_data' => 'Invalid data provided.',

    /*
    |--------------------------------------------------------------------------
    | Authentication Errors
    |--------------------------------------------------------------------------
    */
    'unauthenticated' => 'Authentication required. Please login to continue.',
    'invalid_credentials' => 'Invalid credentials provided.',
    'invalid_token' => 'Invalid or expired authentication token.',
    'token_expired' => 'Your session has expired. Please login again.',

    /*
    |--------------------------------------------------------------------------
    | Authorization Errors
    |--------------------------------------------------------------------------
    */
    'forbidden' => 'Access denied. You do not have permission to perform this action.',
    'unauthorized' => 'You are not authorized to access this resource.',

    /*
    |--------------------------------------------------------------------------
    | Not Found & Conflict Errors
    |--------------------------------------------------------------------------
    */
    'not_found' => 'The requested resource was not found.',
    'conflict' => 'The request conflicts with an existing resource.',
    'gone' => 'The requested resource is no longer available.',

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Timeout
    |--------------------------------------------------------------------------
    */
    'rate_limit' => 'Too many requests. Please try again later.',
    'timeout' => 'The request timed out. Please try again.',

    /*
    |--------------------------------------------------------------------------
    | Server Errors
    |--------------------------------------------------------------------------
    */
    'error' => 'An unexpected error occurred.',
    'server_error' => 'An internal server error occurred. Please try again later.',
    'service_unavailable' => 'Service is temporarily unavailable. Please try again later.',

    /*
    |--------------------------------------------------------------------------
    | Business Logic Errors
    |--------------------------------------------------------------------------
    */
    'operation_failed' => 'The operation could not be completed.',
    'duplicate_entry' => 'This resource already exists.',

    /*
    |--------------------------------------------------------------------------
    | Patient Module
    |--------------------------------------------------------------------------
    */
    'patient' => [
        'created' => 'Patient registered successfully.',
        'updated' => 'Patient information updated successfully.',
        'deleted' => 'Patient record deleted successfully.',
        'not_found' => 'Patient record not found.',
        'invalid_data' => 'Invalid patient data provided.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Browse Doctors (Patient Side)
    |--------------------------------------------------------------------------
    */
    'browse_doctors' => [
        'success' => 'Doctors fetched successfully.',
        'not_found' => 'No doctors found.',
        'unavailable' => 'Doctors are currently unavailable.',
        'invalid_data' => 'Invalid doctor data provided.',
    ],

    'browse_single_doctor' => [
        'success' => 'Doctor profile fetched successfully.',
        'not_found' => 'Doctor not found.',
        'unavailable' => 'Doctor is currently unavailable.',
        'invalid_data' => 'Invalid doctor data provided.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Doctor Module
    |--------------------------------------------------------------------------
    */
    'doctor' => [
        'created' => 'Doctor profile created successfully.',
        'updated' => 'Doctor profile updated successfully.',
        'deleted' => 'Doctor profile deleted successfully.',
        'not_found' => 'Doctor not found.',
        'unavailable' => 'Doctor is currently unavailable.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prescription Module
    |--------------------------------------------------------------------------
    */
    'prescription' => [
        'not_found' => 'There is no prescription for this appointment.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Appointment Module
    |--------------------------------------------------------------------------
    */
    'appointment' => [
        'created' => 'Appointment booked successfully.',
        'updated' => 'Appointment updated successfully.',
        'rescheduled' => 'Appointment rescheduled successfully.',
        'cancelled' => 'Appointment cancelled successfully.',
        'not_found' => 'Appointment not found.',
        'conflict' => 'Selected time slot is already booked.',
        'expired' => 'Past appointments cannot be modified.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Patient Dashboard
    |--------------------------------------------------------------------------
    */
    'patient_home' => [
        'dashboard' => 'Patient dashboard data fetched successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Module
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_success' => 'Login successful.',
        'logout_success' => 'Logout successful.',
        'registered' => 'Registration completed successfully.',
        'email_verified' => 'Email verified successfully.',
        'password_reset' => 'Password reset successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | V3 Multi-step Auth Module
    |--------------------------------------------------------------------------
    */
    'email_already_registered' => 'This email is already registered and active. Please login.',
    'email_already_verified' => 'Email is already verified. Please complete your profile.',
    'verification_code_sent' => 'Verification code sent to your email.',
    'email_verified_successfully' => 'Email verified successfully. You can now complete your profile.',
    'email_not_verified' => 'Please verify your email before completing profile. You can do this by clicking the link sent to your email.',
    'profile_completed' => 'Profile completed and account created successfully. You can now login.',
    'profile_completion_failed' => 'Profile completion failed. Please try again.',
    'registration_not_found' => 'No registration found for this email. Please try again.',
    'registration_status' => 'Registration status fetched successfully. You can now complete your profile.',
    'otp_verified' => 'OTP verified successfully. You can now reset your password.',
    'password_reset_success' => 'Your password has been reset successfully. Login with your new password.',
    'password_changed_successfully' => 'Your password has been changed successfully. Login with your new password.',
    'invalid_current_password' => 'The current password you entered is incorrect. Please try again.',
    'login_success' => 'Login successful. You can now access your account.',
    'logout_success' => 'Logged out successfully.',
    'email_not_registered' => 'This email is not registered. Please try again.',
];
