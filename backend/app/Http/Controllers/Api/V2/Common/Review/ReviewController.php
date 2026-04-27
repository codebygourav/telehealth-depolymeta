<?php

namespace App\Http\Controllers\Api\V2\Common\Review;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reviews\DoctorReviewResource;
use App\Models\{Doctor, DoctorReview, Patient, Appointment};
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Validator;
use App\Enums\AppointmentStatus;


class ReviewController extends Controller
{
    public function index(Request $request, string $id)
    {
        $perPage   = $request->get('per_page', 10);
        $featured  = $request->get('featured');
        $sortBy    = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $patient = $request->user()?->patient;

        $canViewDoctorReviews = Doctor::query()
            ->whereKey($id)
            ->visibleInMobileApp($patient, includeBookedHiddenDoctors: true)
            ->exists();

        if (! $canViewDoctorReviews) {
            return ApiResponseService::notFound(resource: 'Doctor');
        }

        $query = DoctorReview::with([
            'patient.user',
            'patient',
            'doctor.user',
            'doctor' => function ($q) {
                $q->withCount(['reviews as total_reviews' => function ($query) {
                    $query->where('is_active', true);
                }])
                    ->withAvg(['reviews as average_rating' => function ($query) {
                        $query->where('is_active', true);
                    }], 'rating');
            }
        ])
            ->where('doctor_id', $id) // ✅ ALWAYS filter by doctor
            ->where('is_active', true);

        if ($featured !== null) {
            $query->where('is_featured', filter_var($featured, FILTER_VALIDATE_BOOLEAN));
        }

        switch ($sortBy) {
            case 'rating':
                $query->orderBy('rating', $sortOrder)
                    ->orderBy('created_at', 'desc');
                break;

            case 'featured':
                $query->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
                break;

            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        $reviews = $query->paginate($perPage);

        return ApiResponseService::paginated(
            $reviews->through(fn($review) => new DoctorReviewResource($review))
        );
    }


    public function myReviews(Request $request)
    {
        $user = $request->user();
        $patient = Patient::where('user_id', $user->id)->first();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (! $patient && ! $doctor) {
            return ApiResponseService::unauthorized();
        }

        // Get query parameters
        $perPage = $request->get('per_page', 1);
        $featured = $request->get('featured'); // true/false or null for all
        $sortBy = $request->get('sort_by', 'created_at'); // created_at, rating, featured
        $sortOrder = $request->get('sort_order', 'desc'); // asc, desc

        // Build query: patient sees their reviews as patient, doctor sees theirs as doctor
        $query = DoctorReview::with([
            'patient.user',
            'patient',
            'doctor.user',
            'doctor' => function ($q) {
                $q->withCount(['reviews as total_reviews' => function ($query) {
                    $query->where('is_active', true);
                }])
                    ->withAvg(['reviews as average_rating' => function ($query) {
                        $query->where('is_active', true);
                    }], 'rating');
            }
        ])->where('is_active', true);

        // If doctor, filter by doctor_id
        if ($doctor) {
            $query->where('doctor_id', $doctor->id);
        }
        // If patient and NOT doctor (ignore patient filter if both maybe, but prioritize doctor if both exists)
        elseif ($patient) {
            $query->where('patient_id', $patient->id);
        }

        // Filter by featured if specified
        if ($featured !== null) {
            $query->where('is_featured', filter_var($featured, FILTER_VALIDATE_BOOLEAN));
        }

        // Apply sorting
        switch ($sortBy) {
            case 'rating':
                $query->orderBy('rating', $sortOrder)
                    ->orderBy('created_at', 'desc'); // Secondary sort by date
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        // Get paginated results
        $reviews = $query->paginate($perPage);

        $reviews->setCollection(
            DoctorReviewResource::collection($reviews->items())->collection
        );

        return ApiResponseService::paginated($reviews);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|uuid|exists:users,id',
            'appointment_id' => 'required|uuid|exists:appointments,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $validated = $validator->validated();

        $user = $request->user();

        if (! $user) {
            return ApiResponseService::unauthorized();
        }

        $patient = Patient::where('user_id', $user->id)->first();

        if (! $patient) {
            return ApiResponseService::unauthorized();
        }

        $doctor = Doctor::where('user_id', $validated['doctor_id'])->first();

        if (! $doctor) {
            return ApiResponseService::notFound();
        }

        $appointment = Appointment::where('id', $validated['appointment_id'])
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (! $appointment) {
            return ApiResponseService::validationError('Invalid appointment selected.');
        }

        if ($appointment->status !== AppointmentStatus::COMPLETED) {
            return ApiResponseService::validationError('You can only review completed appointments.');
        }

        if (DoctorReview::where('appointment_id', $appointment->id)->exists()) {
            return ApiResponseService::validationError('This appointment has already been reviewed.');
        }

        $review = DoctorReview::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'is_active' => true,
        ]);

        NotificationService::notifyNewReview($review);
        return ApiResponseService::created(
            'responses.created',
            new DoctorReviewResource($review->load(['doctor', 'patient']))
        );
    }
}
