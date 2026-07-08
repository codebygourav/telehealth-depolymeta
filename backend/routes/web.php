<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\VendorRegistration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Mail\DoctorCredentialsMail;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\PatientNotificationController;

Route::view('/test-upload', 'test-upload');
Route::view('/test-patient-registration', 'test-patient-registration');

Route::get('/test-mail', function () {
    Mail::to('webclouddeveloper@gmail.com')->send(new DoctorCredentialsMail('Test Doctor', 'webclouddeveloper@gmail.com', '123456'));
    return 'Mail sent';
});
Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/prescriptions/{id}', function ($id) {
    return view('Prescription.prescription');
});

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return "All cache cleared!";
});
// Route::get('/fresh-seed-force', function () {

//     Artisan::call('migrate:fresh', [
//         '--seed' => true,
//         '--force' => true
//     ]);

//     return Artisan::output();
// });

Route::get('/vendor/register', VendorRegistration::class)->name('vendor.register');

Route::get('/thankyou-demo', function () {
    return view('templates.thankyou');
});

// Admin: notify the patient about their next upcoming appointment (no API change).
Route::post('/admin/patients/{patient}/notify-next', [PatientNotificationController::class, 'notifyNextAppointment'])
    ->middleware(['web', 'auth'])
    ->name('admin.patients.notify-next');

// Admin: Web Push Notification Subscriptions
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/admin/webpush/subscribe', [\App\Http\Controllers\WebPushController::class, 'store'])
        ->name('admin.webpush.subscribe');
    Route::post('/admin/webpush/unsubscribe', [\App\Http\Controllers\WebPushController::class, 'destroy'])
        ->name('admin.webpush.unsubscribe');
});

//  to make the storage symlink work, run the following command on live using SSH:
// cd public
// rm -rf storage
// ln -s ../storage/app/public storage