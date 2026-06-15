<?php

use App\Http\Controllers\Api\V2\Common\Appointment\AppointmentController;
use App\Http\Controllers\Api\V2\Common\Appointment\BookAppointmentController;
use App\Http\Controllers\Api\V2\Common\ContactUs\ContactUsController;
use App\Http\Controllers\Api\V2\Common\Department\DepartmentController;
use App\Http\Controllers\Api\V2\Common\Leave\LeaveController;
use App\Http\Controllers\Api\V2\Common\Notification\NotificationController;
use App\Http\Controllers\Api\V2\Common\Prescription\PrescriptionController;
use App\Http\Controllers\Api\V2\Common\Review\ReviewController;
use App\Http\Controllers\Api\V2\Common\VideoConsultation\WherebyWebhookController;
use App\Http\Controllers\Api\V2\Doctor\DoctorController;
use App\Http\Controllers\Api\V2\Doctor\DoctorHomeController;
use App\Http\Controllers\Api\V2\Doctor\MedicineController;
use App\Http\Controllers\Api\V2\Doctor\PatientBrowserController;
use App\Http\Controllers\Api\V2\Doctor\UsageAnalyticsController;
use App\Http\Controllers\Api\V2\Patient\DoctorBrowseController;
use App\Http\Controllers\Api\V2\Patient\PatientHomeController;
use App\Http\Controllers\Api\V2\Patient\PatientMedicalReportController;
use App\Http\Controllers\Api\V2\Patient\PatientProfileController;
use App\Http\Controllers\Api\V2\Patient\TransactionsController;
use App\Http\Controllers\Api\V2\Wordpress\DepartmentController as WordpressDepartmentController;
use App\Services\ApiResponseService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Route;



Route::post('/whereby/webhook', [WherebyWebhookController::class, 'handle']);
Route::get('/settings', function () {
    return ApiResponseService::success(responseKey: 'responses.success', data: SettingService::getPublicSettings());
});

Route::get('/app-profile-screens', function () {
    return ApiResponseService::success(responseKey: 'responses.success', data: SettingService::getProfileScreenContent());
});
// WordPress routes for website integration
Route::prefix('wordpress')->group(function () {
    Route::get('/departments', [WordpressDepartmentController::class, 'index']);
    Route::get('/departments/{slug}', [WordpressDepartmentController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    // api used in patient screen (doctor profile, appointments, specialities and symptoms, all doctors, patient profile, doctor reviews etc)
    Route::prefix('patient')->group(function () {
        Route::get('/home', [PatientHomeController::class, 'index']);
        Route::get('/{user_id}/profile', [PatientProfileController::class, 'show']);
        Route::get('/browse-doctors', [DoctorBrowseController::class, 'index']);
        Route::get('/browse-doctor/{user_id}', [DoctorBrowseController::class, 'show']);
        Route::get('/departments-and-symptoms-list', [DoctorBrowseController::class, 'getDepartmentsAndSymptomsList']);
        Route::get('/my-transactions', [TransactionsController::class, 'index']); // get all transactions for the patient
        Route::get('/transactions/{id}', [TransactionsController::class, 'show']);
        Route::post('/{user_id}', [PatientProfileController::class, 'update']);
        // Medical reports: list, upload, delete
        Route::get('/{user_id}/medical-reports', [PatientMedicalReportController::class, 'index']);
        Route::post('/{user_id}/medical-reports', [PatientMedicalReportController::class, 'store']);
        Route::delete('/medical-reports/{appointmentId}/{report}', [PatientMedicalReportController::class, 'medicalReportDeleteForAppointment']);
        Route::delete('/medical-reports/{report}', [PatientMedicalReportController::class, 'destroy']);
    });
    Route::prefix('prescriptions')->group(function () {
        Route::get('/{id}', [PrescriptionController::class, 'getPrescriptionByUser']);
        Route::get('/detail/{appointmentid}', [PrescriptionController::class, 'show']);
        Route::get('/download/{appointmentid}', [PrescriptionController::class, 'download']);
    });

    Route::prefix('doctor')->group(function () {
        Route::get('/get-profile', [DoctorController::class, 'getProfile']);
        Route::get('/home', [DoctorHomeController::class, 'index']);
        Route::get('/all-patients', [DoctorController::class, 'appointments']);
        Route::get('/all-reports', [DoctorController::class, 'getPatientReports']);
        Route::get('/{id}/get-slot-detail', [DoctorController::class, 'getOwnSlotForReschdule']);
        Route::get('/schedule', [DoctorController::class, 'schedule']); // get doctor schedule with day/week/month filter. Query params: filter=day|week|month, date=Y-m-d (filter=day&date=2025-11-25)
        Route::get('/usage-analytics', [UsageAnalyticsController::class, 'index']); // get usage analytics with appointment statistics. Query params: period=week|month|year (default: month)
        Route::get('/patient-detail/{appointment:id}', [PatientBrowserController::class, 'show']);
        Route::get('/medicines', [PrescriptionController::class, 'index']);
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/{user_id}', [DoctorController::class, 'show']); // get doctor profile
        Route::post('/{user_id}', [DoctorController::class, 'update']); // update doctor profile
        Route::post('{appointmentId}/prescriptions', [PrescriptionController::class, 'store']);
    });
    Route::prefix('medicines')->group(function () {
        Route::get('/', [MedicineController::class, 'index']);
    });
    // Reviews routes where patient can see all reviews and doctor can see their own reviews
    Route::prefix('reviews')->group(function () {
        Route::get('/my', [ReviewController::class, 'myReviews']); // get reviews: doctors see their own reviews, patients see all reviews. Query params: per_page, featured, sort_by, sort_order
        Route::get('/all-reviews/{id}', [ReviewController::class, 'index']);
        Route::post('/', [ReviewController::class, 'store']); // create a new review for the doctor
        Route::put('/{id}', [ReviewController::class, 'update']); // edit a review
        Route::delete('/{id}', [ReviewController::class, 'destroy']); // delete a review
    });
    Route::post('/contact-us', [ContactUsController::class, 'store']);
    Route::prefix('appointments')->group(function () {
        Route::get('/my', [AppointmentController::class, 'myAppointments']); // get all appointments for the user (patient or doctor)
        Route::get('/doctor-instructions/{appointmentId}', [AppointmentController::class, 'getDoctorInstructions']); // get doctor instructions for an appointment
        Route::post('/doctor-instructions/{appointmentId}', [AppointmentController::class, 'storeDoctorInstructions']);
        Route::get('/{id}', [AppointmentController::class, 'show']); // get a single appointment by id. Patients can view their own appointments, doctors can view their own appointments (both past and future)
        Route::post('/', [AppointmentController::class, 'store']); // create a new appointment for the user (patient only)
        Route::post('/{appointmentId}/update-information', [AppointmentController::class, 'updateNotesAndReport']); // update appointment notes and/or upload medical report file
        Route::post(
            '/{appointmentId}/mark-as-completed',
            [AppointmentController::class, 'markAsCompleted']
        );
    });
    Route::prefix('appointments')->group(function () {
        Route::post('/cancel', [BookAppointmentController::class, 'cancel']); // cancel an appointment for the user (patient or doctor)
        Route::post('/reschedule', [BookAppointmentController::class, 'reschedule']); // reschedule an appointment for the user (patient only)
    });
    Route::post('/book-appointment', [BookAppointmentController::class, 'book']);
    Route::post('/verify-payment', [BookAppointmentController::class, 'verifyPayment'])
        ->middleware('throttle:verify-payment');

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/{notificationId}', [NotificationController::class, 'show']);
        Route::post('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('/{notificationId}/archive', [NotificationController::class, 'archive']);
        Route::post('/{notificationId}/unarchive', [NotificationController::class, 'unarchive']);
        Route::post('/archive-all', [NotificationController::class, 'archiveAll']);
    });

    Route::prefix('devices')->group(function () {
        Route::post('/refresh', [\App\Http\Controllers\Api\V2\Common\Device\UserDeviceController::class, 'refresh']);
        Route::post('/deactivate', [\App\Http\Controllers\Api\V2\Common\Device\UserDeviceController::class, 'deactivate']);
    });


    // Leave routes (common for all roles: doctor, patient, staff, etc.)
    Route::prefix('leave')->group(function () {
        Route::get('/my', [LeaveController::class, 'index']); // Get my leaves
        Route::post('/', [LeaveController::class, 'store']); // Apply for leave
        Route::get('/{id}', [LeaveController::class, 'show']); // Get a single leave by id
    });
});
