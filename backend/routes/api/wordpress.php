<?php

use App\Http\Controllers\Api\V2\Wordpress\DepartmentController;
use App\Http\Controllers\Api\V2\Wordpress\DoctorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\Wordpress\PatientController;
use App\Http\Middleware\VerifyWordpressApi;

Route::prefix('wordpress')->middleware(VerifyWordpressApi::class)->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{slug}', [DepartmentController::class, 'show']);
});
Route::middleware(VerifyWordpressApi::class)->group(function () {
    Route::get('/doctors/minimal', [DoctorController::class, 'index']);
    Route::get('/doctors/{slug}', [DoctorController::class, 'show']);
});
Route::prefix('patient')->group(function () {
    Route::post('/', [PatientController::class, 'store']);
});