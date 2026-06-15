<?php

namespace App\Http\Controllers\Api\V2\Wordpress;

use App\Http\Controllers\Controller;
use App\Http\Resources\WordPress\DoctorMinimalResource;
use App\Http\Resources\WordPress\DoctorResource;
use App\Models\Doctor;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = $this->doctorQuery(testDoctorsOnly: $request->boolean('test'))
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated(
            $doctors->through(fn($doctor) => new DoctorMinimalResource($doctor))
        );
    }

    public function show(Request $request, string $slug)
    {
        $doctor = $this->doctorQueryForShow(testDoctorsOnly: $request->boolean('test'))
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponseService::success(
            data: new DoctorResource($doctor)
        );
    }

    private function doctorQueryForShow(bool $testDoctorsOnly)
    {
        return Doctor::with(['user', 'departments', 'availabilities' => function ($query) use ($testDoctorsOnly) {
            $query->with('overrides')
                ->when(! $testDoctorsOnly, fn ($query) => $query->withoutTestDoctors());
        }])
            ->where('status', \App\Enums\DoctorStatus::ACTIVE->value)
            ->visibleInWordPressApi()
            ->when(
                $testDoctorsOnly,
                fn ($query) => $query->where('is_test_doctor', true),
                fn ($query) => $query->withoutTestDoctors()
            )
            ->whereHas('availabilities', function ($query) use ($testDoctorsOnly) {
                $query->when(! $testDoctorsOnly, fn ($query) => $query->withoutTestDoctors());
            })
            ->orderBy('first_name')
            ->orderBy('last_name');
    }

    private function doctorQuery(bool $testDoctorsOnly)
    {
        return Doctor::with(['user', 'departments', 'availabilities' => function ($query) use ($testDoctorsOnly) {
            $query->where('is_available', true)
                ->with('overrides')
                ->when(! $testDoctorsOnly, fn($query) => $query->withoutTestDoctors());
        }])
            ->where('status', \App\Enums\DoctorStatus::ACTIVE->value)
            ->visibleInWordPressApi()
            ->when(
                $testDoctorsOnly,
                fn($query) => $query->where('is_test_doctor', true),
                fn($query) => $query->withoutTestDoctors()
            )
            ->whereHas('availabilities', function ($query) use ($testDoctorsOnly) {
                $query->where('is_available', true)
                    ->when(! $testDoctorsOnly, fn($query) => $query->withoutTestDoctors());
            })
            ->orderBy('first_name')
            ->orderBy('last_name');
    }
}
