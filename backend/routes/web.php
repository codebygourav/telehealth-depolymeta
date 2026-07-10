<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DisplayTokenController;
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
Route::get('/opd-token', [DisplayTokenController::class, 'show'])->name('opd-token.display');
Route::get('/opd-token/data', [DisplayTokenController::class, 'boardData'])->name('opd-token.data');
Route::post('/opd-token/authenticate', [DisplayTokenController::class, 'authenticate'])->name('opd-token.authenticate');
Route::post('/opd-token/logout', [DisplayTokenController::class, 'logout'])->name('opd-token.logout');
Route::get('/opd-token/{screen:slug}', [DisplayTokenController::class, 'show'])->name('opd-token.screen.display');
Route::get('/opd-token/{screen:slug}/data', [DisplayTokenController::class, 'boardData'])->name('opd-token.screen.data');
Route::post('/opd-token/{screen:slug}/authenticate', [DisplayTokenController::class, 'authenticate'])->name('opd-token.screen.authenticate');
Route::post('/opd-token/{screen:slug}/logout', [DisplayTokenController::class, 'logout'])->name('opd-token.screen.logout');

// Backward-compatible aliases for old OPT URLs.
Route::get('/opt-token', [DisplayTokenController::class, 'show'])->name('opt-token.display');
Route::get('/opt-token/data', [DisplayTokenController::class, 'boardData'])->name('opt-token.data');
Route::post('/opt-token/authenticate', [DisplayTokenController::class, 'authenticate'])->name('opt-token.authenticate');
Route::post('/opt-token/logout', [DisplayTokenController::class, 'logout'])->name('opt-token.logout');
Route::get('/opt-token/{screen:slug}', [DisplayTokenController::class, 'show'])->name('opt-token.screen.display');
Route::get('/opt-token/{screen:slug}/data', [DisplayTokenController::class, 'boardData'])->name('opt-token.screen.data');
Route::post('/opt-token/{screen:slug}/authenticate', [DisplayTokenController::class, 'authenticate'])->name('opt-token.screen.authenticate');
Route::post('/opt-token/{screen:slug}/logout', [DisplayTokenController::class, 'logout'])->name('opt-token.screen.logout');
Route::get('/queue', [DisplayTokenController::class, 'show'])->name('queue.display');

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
