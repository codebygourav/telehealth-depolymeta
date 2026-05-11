<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\Common\Appointment\BookAppointmentController;



Route::get('/user', fn() => response('Simple text'));
Route::post('/razorpay/webhook', [BookAppointmentController::class, 'razorpayWebhook']);

Route::prefix('v2')->group(function () {
    require __DIR__ . '/api/app.php';
    Route::post('/test-book-appointment', [BookAppointmentController::class, 'book']);
    Route::post('/test-verify-payment', [BookAppointmentController::class, 'verifyPayment'])
        ->middleware('throttle:verify-payment');
    require __DIR__ . '/api/auth.php';
    require __DIR__ . '/api/wordpress.php';
});
