<?php

use App\Http\Controllers\Api\V2\Wordpress\DepartmentController;
use App\Http\Controllers\Api\V2\Wordpress\DoctorController;
use Illuminate\Support\Facades\Route;


Route::prefix('wordpress')->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{slug}', [DepartmentController::class, 'show']);
});
Route::get('/doctors/minimal', [DoctorController::class, 'index']);

Route::get('/doctors/{slug}', [DoctorController::class, 'show']);
Route::prefix('patient')->group(function () {
    Route::post('/', [PatientController::class, 'store']);
});
