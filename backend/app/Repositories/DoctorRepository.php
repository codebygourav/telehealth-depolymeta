<?php

namespace App\Repositories;

use App\Models\Doctor;
use App\Models\Symptom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DoctorRepository
{
    /**
     * Get available doctors based on a date range and sorting.
     */
    public function getAvailableDoctors(array $params = [])
    {
        $perPage = $params['per_page'] ?? 10;
        $sortBy = $params['sort_by'] ?? 'earliest_availability';
        $today = Carbon::today();
        $fifteenDaysLater = $today->copy()->addDays(14);

        $query = Doctor::query()
            ->select([
                'doctors.id',
                'doctors.user_id',
                'doctors.first_name',
                'doctors.last_name',
                'doctors.status',
                'doctors.slug',
                'doctors.education_info',
                'doctors.years_experience',
                'doctors.languages_known',
            ])
            ->active()
            ->visibleInMobileApp()
            ->withAvailability($today->toDateString(), $fifteenDaysLater->toDateString())
            ->with([
                'departments:id,name,slug,symptom_ids',
                'availabilities' => fn($q) => $q->availableInRange($today->toDateString(), $fifteenDaysLater->toDateString())->with('overrides'),
                'user:id,name,email,phone',
            ])
            ->withCount([
                'reviews as total_reviews' => fn($q) => $q->where('is_active', true),
            ])
            ->withAvg([
                'reviews as average_rating' => fn($q) => $q->where('is_active', true),
            ], 'rating');

        // Earliest Availability Subquery
        $earliestAvailabilitySubquery = \App\Models\DoctorAvailability::query()
            ->selectRaw('MIN(CASE
                WHEN is_recurring = 0 AND date IS NOT NULL THEN date
                WHEN is_recurring = 0 AND date IS NULL AND day_of_week IS NOT NULL THEN ?
                WHEN is_recurring = 1 AND recurring_start_date > ? THEN recurring_start_date
                ELSE ?
            END)', [$today->toDateString(), $today->toDateString(), $today->toDateString()])
            ->whereColumn('doctor_id', 'doctors.id')
            ->availableInRange($today->toDateString(), $fifteenDaysLater->toDateString());

        $query->selectSub($earliestAvailabilitySubquery, 'earliest_availability');

        // Apply Sorting
        if ($sortBy === 'earliest_availability') {
            $query->orderBy('earliest_availability', 'asc');
        } elseif ($sortBy === 'rating') {
            $query->orderByDesc('average_rating');
        } elseif ($sortBy === 'popularity') {
            $query->orderByDesc('total_reviews');
        } else {
            $query->orderByDesc('doctors.created_at');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get available doctors with smart ranking for home screen.
     */
    public function getAvailableDoctorsWithSmartRanking(int $limit = 5)
    {
        $now = now();
        $twoDaysLater = $now->copy()->addDays(2)->toDateString();
        $todayStr = $now->toDateString();

        return Doctor::query()
            ->select('id', 'user_id', 'first_name', 'last_name', 'status', 'years_experience', 'languages_known')
            ->active()
            ->visibleInMobileApp()
            ->whereHas('availabilities', fn($q) => $q->where('is_available', true))
            ->withCount(['reviews as total_reviews' => fn($q) => $q->where('is_active', true)])
            ->withAvg(['reviews as average_rating' => fn($q) => $q->where('is_active', true)], 'rating')
            ->with([
                'departments:id,name',
                'availabilities' => fn($q) => $q->where('is_available', true)->with('overrides')->orderBy('consultation_fee'),
            ])
            ->selectRaw('
                (COALESCE((SELECT AVG(rating) FROM doctor_reviews WHERE doctor_id = doctors.id AND is_active = 1), 0) * 2) +
                (CASE WHEN EXISTS (
                    SELECT 1 FROM availabilities
                    WHERE doctor_id = doctors.id
                    AND is_available = 1
                    AND (
                        (date IS NOT NULL AND date <= ?) OR
                        (date IS NULL AND day_of_week IS NOT NULL) OR
                        (is_recurring = 1 AND (
                            (recurring_start_date IS NULL OR recurring_start_date <= ?) AND
                            (recurring_end_date IS NULL OR recurring_end_date >= ?)
                        ))
                    )
                ) THEN 2 ELSE 0 END) +
                (CASE WHEN (SELECT COUNT(*) FROM doctor_reviews WHERE doctor_id = doctors.id AND is_active = 1) > 5 THEN 1 ELSE 0 END)
                as smart_score
            ', [$twoDaysLater, $todayStr, $todayStr])
            ->orderByDesc('smart_score')
            ->orderByDesc('average_rating')
            ->limit($limit)
            ->get();
    }

    /**
     * Get a single doctor profile with all details.
     */
    public function getDoctorProfile(string $user_id)
    {
        $today = today();
        $endDate = $today->copy()->addDays(14)->toDateString();
        $todayDate = $today->toDateString();
        $now = now();
        $nowTime = $now->toTimeString();

        $patient = request()->user()?->patient;

        return Doctor::query()
            ->select([
                'id',
                'user_id',
                'first_name',
                'last_name',
                'slug',
                'description',
                'education_info',
                'years_experience',
                'fellowships_info',
                'languages_known',
                'bio',
                'status',
                'hide_from_mobile_app',
            ])
            ->where('user_id', $user_id)
            ->active()
            ->visibleInMobileApp($patient, includeBookedHiddenDoctors: true)
            ->withCount(['reviews as total_reviews' => fn($q) => $q->where('is_active', true)])
            ->withAvg(['reviews as average_rating' => fn($q) => $q->where('is_active', true)], 'rating')
            ->with([
                'departments:id,name,slug,symptom_ids',
                'user:id,name,email,phone',
                'reviews' => function ($q) {
                    $q->select('id', 'doctor_id', 'patient_id', 'title', 'content', 'rating', 'is_featured', 'review_type', 'faker_patient_id', 'created_at')
                        ->where('is_active', true)
                        ->with([
                            'patient:id,user_id,first_name,last_name,date_of_birth,address',
                            'patient.user:id,name',
                            'doctor:id,user_id,first_name,last_name',
                            'doctor.user:id,name',
                            'fakerPatient:id,name,age,address',
                        ])
                        ->orderByDesc('is_featured')
                        ->orderByDesc('created_at')
                        ->limit(3);
                },
                'availabilities' => function ($q) use ($todayDate, $endDate) {
                    $q->select('id', 'doctor_id', 'date', 'is_available', 'is_recurring', 'recurring_start_date', 'recurring_end_date', 'start_time', 'end_time', 'consultation_type', 'day_of_week', 'capacity', 'consultation_fee', 'opd_type', 'doctor_room', 'blocked_dates')
                        ->with('overrides')
                        ->availableInRange($todayDate, $endDate)
                        ->orderBy('start_time');
                },
            ])
            ->first();
    }

    /**
     * Get featured reviews with aggregated doctor stats.
     */
    public function getFeaturedReviewsWithStats(int $limit = 5)
    {
        $reviews = \App\Models\DoctorReview::active()
            ->featured()
            ->whereHas('doctor', fn($query) => $query->visibleInMobileApp())
            ->with([
                'patient:id,date_of_birth,address,first_name,last_name',
                'fakerPatient:id,name,age,address',
                'doctor.user:id,name',
                'doctor:id,user_id',
            ])
            ->latest()
            ->limit($limit)
            ->get();

        $doctorIds = $reviews->pluck('doctor_id')->filter()->unique();
        $ratingsAgg = \App\Models\DoctorReview::query()
            ->selectRaw('doctor_id, COUNT(*) as total_reviews, AVG(rating) as average_rating')
            ->active()
            ->whereIn('doctor_id', $doctorIds)
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        foreach ($reviews as $review) {
            $stats = $ratingsAgg->get($review->doctor_id);
            $review->total_reviews = $stats ? (int) $stats->total_reviews : 0;
            $review->average_rating = $stats ? (float) $stats->average_rating : null;
        }

        return $reviews;
    }

    /**
     * Map symptoms for specific models.
     */
    public function getSymptomsMap(iterable $models, string $attribute = 'departments')
    {
        $allSymptomIds = collect($models)
            ->pluck($attribute)
            ->flatten()
            ->pluck('symptom_ids')
            ->flatten()
            ->unique()
            ->filter();

        return Symptom::whereIn('id', $allSymptomIds)->get()->keyBy('id');
    }
}
