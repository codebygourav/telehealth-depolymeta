<?php

namespace App\Http\Controllers\Api\V2\Wordpress;

use App\Http\Controllers\Controller;
use App\Http\Resources\WordPress\DoctorMinimalResource;
use App\Http\Resources\WordPress\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        // Use "status" and order by "first_name" and "last_name" since "name" column does not exist.
        $doctors = Doctor::with(['user', 'departments', 'availabilities'])
            ->where('status', \App\Enums\DoctorStatus::ACTIVE->value)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($request->integer('per_page', 10));

        return ApiResponseService::paginated(
            $doctors->through(fn($doctor) => new DoctorMinimalResource($doctor))
        );
    }
    public function show(Request $request, string $slug)
    {
        $doctor = Doctor::with([
                'user',
                'departments',
                'availabilities'
            ])
            ->whereHas('user', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->firstOrFail();

        return ApiResponseService::success(
            data: new DoctorResource($doctor)
        );
    }
}
