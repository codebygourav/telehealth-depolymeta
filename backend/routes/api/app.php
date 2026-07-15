<?php

use App\Http\Controllers\Api\V2\Common\Appointment\AppointmentController;
use App\Http\Controllers\Api\V2\Common\Appointment\BookAppointmentController;
use App\Http\Controllers\Api\V2\Common\ContactUs\ContactUsController;
use App\Http\Controllers\Api\V2\Common\Department\DepartmentController;
use App\Http\Controllers\Api\V2\Common\Leave\LeaveController;
use App\Http\Controllers\Api\V2\Common\Notification\NotificationController;
use App\Http\Controllers\Api\V2\Common\Notification\PushSubscriptionController;
use App\Http\Controllers\Api\V2\Common\Prescription\PrescriptionController;
use App\Http\Controllers\Api\V2\Common\Review\ReviewController;
use App\Http\Controllers\Api\V2\Common\VideoConsultation\WherebyWebhookController;
use App\Http\Controllers\Api\V2\Doctor\DoctorController;
use App\Http\Controllers\Api\V2\Doctor\DoctorHomeController;
use App\Http\Controllers\Api\V2\Doctor\MedicineController;
use App\Http\Controllers\Api\V2\Doctor\MedicineTemplateController;
use App\Http\Controllers\Api\V2\Doctor\PatientBrowserController;
use App\Http\Controllers\Api\V2\Doctor\UsageAnalyticsController;
use App\Http\Controllers\Api\V2\Patient\DoctorBrowseController;
use App\Http\Controllers\Api\V2\Patient\PatientHomeController;
use App\Http\Controllers\Api\V2\Patient\PatientMedicalReportController;
use App\Http\Controllers\Api\V2\Patient\TransactionsController;
use App\Http\Controllers\Api\V2\Wordpress\DepartmentController as WordpressDepartmentController;
use App\Services\ApiResponseService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V2\Doctor\DietTemplateController;
use App\Http\Controllers\Api\V2\Doctor\PatientDietController;
use App\Http\Controllers\Api\V2\Doctor\PatientVaccinationController;
use App\Http\Controllers\Api\V2\Doctor\VaccinationController;
use App\Http\Controllers\Api\V2\Doctor\VaccinationTemplateController;
use App\Http\Controllers\Api\V2\Vaccination\VaccinationModuleContentController;



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
        Route::get('/browse-doctors', [DoctorBrowseController::class, 'index']);
        Route::get('/browse-doctor/{user_id}', [DoctorBrowseController::class, 'show']);
        Route::get('/departments-and-symptoms-list', [DoctorBrowseController::class, 'getDepartmentsAndSymptomsList']);
        Route::get('/my-transactions', [TransactionsController::class, 'index']); // get all transactions for the patient
        Route::get('/transactions/{id}', [TransactionsController::class, 'show']);
        // Medical reports: list, upload, delete
        Route::get('/{user_id}/medical-reports', [PatientMedicalReportController::class, 'index']);
        Route::post('/{user_id}/medical-reports', [PatientMedicalReportController::class, 'store']);
        Route::delete('/medical-reports/{appointmentId}/{report}', [PatientMedicalReportController::class, 'medicalReportDeleteForAppointment']);
        Route::delete('/medical-reports/{report}', [PatientMedicalReportController::class, 'destroy']);
        Route::get('/diet-plan', [PatientDietController::class, 'patientPlan']);
        Route::post('/diet/meal/{mealId}/complete', [PatientDietController::class, 'markMealCompleted']);
        Route::get('/vaccinations', [PatientVaccinationController::class, 'patientVaccinations']);
        Route::get('/vaccination-content', [VaccinationModuleContentController::class, 'index']);
        Route::get('/vaccination-program-assignments', [PatientVaccinationController::class, 'patientPrograms']);
        Route::get('/vaccinations/{id}', [PatientVaccinationController::class, 'patientShow']);
    });
    Route::prefix('prescriptions')->group(function () {
        Route::get('/{id}', [PrescriptionController::class, 'getPrescriptionByUser']);
        Route::get('/detail/{appointmentid}', [PrescriptionController::class, 'show']);
        Route::get('/download/{appointmentid}', [PrescriptionController::class, 'download']);
        Route::delete('/{id}', [PrescriptionController::class, 'destroy']);
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
        Route::post('{appointmentId}/prescriptions', [PrescriptionController::class, 'store']);
        Route::post('{appointmentId}/prescription-drafts/text', [PrescriptionController::class, 'parseTextDraft']);
        Route::get('/medicine-templates', [MedicineTemplateController::class, 'index']);
        Route::get('/medicine-templates/{id}', [MedicineTemplateController::class, 'show']);
        Route::post('/{appointmentId}/assign-medicine-template', [MedicineTemplateController::class, 'assign']);

        // vaccination master lookup only; master creation is admin-side
        Route::get('/vaccinations', [VaccinationController::class, 'index']);
        Route::get('/vaccinations/{id}', [VaccinationController::class, 'show']);

        // diet templates + plan assignment (simple flow)
        Route::get('/diet/templates', [DietTemplateController::class, 'index']);
        Route::post('/diet/templates', [DietTemplateController::class, 'store']);
        Route::get('/diet/templates/{id}', [DietTemplateController::class, 'show']);
        Route::post('/diet/meal/{mealId}/complete', [PatientDietController::class, 'markMealCompleted']);
        Route::match(['put', 'post'], '/diet/templates/{id}', [DietTemplateController::class, 'update']);
        Route::delete('/diet/templates/{id}', [DietTemplateController::class, 'destroy']);
        Route::post('/diet/assign', [PatientDietController::class, 'assign']);
        Route::get('/{patientId}/diet-plan', [PatientDietController::class, 'doctorPatientPlan']);
        Route::match(['put', 'post'], '/diet/plans/{id}', [PatientDietController::class, 'updatePlan']);

        // templates: doctors can view and update existing templates, admin/data-entry creates them
        Route::get('/vaccination-templates', [VaccinationTemplateController::class, 'index']);
        Route::get('/vaccination-templates/{id}', [VaccinationTemplateController::class, 'show']);
        Route::match(['put', 'post'], '/vaccination-templates/{id}', [VaccinationTemplateController::class, 'update']);

        // assign vaccination template
        Route::post(
            '/{patientId}/assign-template',
            [PatientVaccinationController::class, 'assignTemplate']
        );
        Route::post(
            '/patients/{patientId}/assign-custom-vaccination',
            [PatientVaccinationController::class, 'assignCustomVaccination']
        );
        // patient vaccinations
        Route::get('/{patientId}/vaccinations', [PatientVaccinationController::class, 'index']);
        // mark vaccination completed
        Route::post('/patient-vaccinations/complete-multiple', [PatientVaccinationController::class, 'completeMultiple']);
        Route::post('/patient-vaccinations/{id}/complete', [PatientVaccinationController::class, 'markCompleted']);
        Route::post('/patient-vaccinations/{id}/documents', [PatientVaccinationController::class, 'addDocument']);
        Route::delete('/vaccination-documents/{documentId}', [PatientVaccinationController::class, 'deleteDocument']);
        // update vaccination
        Route::match(['put', 'post'], '/patient-vaccinations/{id}', [PatientVaccinationController::class, 'update']);


        Route::get('/{user_id}', [DoctorController::class, 'show']); // get doctor profile
        Route::post('/{user_id}', [DoctorController::class, 'update']); // update doctor profile
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

        // WebPush subscription endpoints
        Route::post('/push-subscription', [PushSubscriptionController::class, 'update']);
        Route::post('/push-subscription/delete', [PushSubscriptionController::class, 'destroy']);
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
