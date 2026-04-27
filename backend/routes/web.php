<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\VendorRegistration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Mail\DoctorCredentialsMail;
use Illuminate\Support\Facades\Http;

Route::view('/test-upload', 'test-upload');

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

//  to make the storage symlink work, run the following command on live using SSH:
// cd public
// rm -rf storage
// ln -s ../storage/app/public storage
