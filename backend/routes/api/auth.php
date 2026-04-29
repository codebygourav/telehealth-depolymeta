<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Api\V2\Auth\{RegisterController, LoginController, OtpVerificationController, ForgotPasswordController, ChangePasswordController, ResendOtpController};
use App\Http\Controllers\Api\V2\Auth\{RegisterController, VerifyEmailController, ProfileController, LoginController, ForgotPasswordController, ResetPasswordController, ChangePasswordController, ResendOtpController};

Route::prefix('auth')->group(function () {
    // Guest Routes
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/verify-email', [VerifyEmailController::class, 'verify']);
    Route::post('/resend-otp', [ResendOtpController::class, 'resendOtp']);
    Route::post('/complete-profile', [ProfileController::class, 'complete']);
    Route::get('/status', [ProfileController::class, 'checkStatus']);


    Route::post('/login', [LoginController::class, 'login']);

    Route::prefix('forgot-password')->group(function () {
        Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp']);
        Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
        Route::post('/reset', [ResetPasswordController::class, 'reset']);
    });

    // Registration wizard helpers (public)
    Route::prefix('registration')->group(function () {
        Route::get('/departments', [ProfileController::class, 'registrationDepartments']);
        Route::get('/doctors', [ProfileController::class, 'registrationDoctors']);
        Route::get('/doctor-availability', [ProfileController::class, 'registrationDoctorAvailability']);
    });

    // Authenticated Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::post('/change-password', [ChangePasswordController::class, 'change']);
    });

});

