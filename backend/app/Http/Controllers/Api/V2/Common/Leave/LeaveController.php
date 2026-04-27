<?php

namespace App\Http\Controllers\Api\V2\Common\Leave;

use App\Http\Controllers\Controller;
use App\Http\Resources\Common\LeaveResource;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\ApiResponseService;

class LeaveController extends Controller
{
    /**
     * Get all leaves for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $leaves = Leave::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10); // Set per page to 10 records

        $leaves->setCollection(
            LeaveResource::collection($leaves->getCollection())->collection
        );

        return ApiResponseService::paginated(
            paginated: $leaves,
            responseKey: 'responses.success'
        );
    }

    /**
     * Apply for a new leave
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:sick,casual,annual,telehealth',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ], [
            'type.required' => 'Leave type is required.',
            'type.in' => 'Invalid leave type. Allowed types: sick, casual, annual, telehealth.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be the same as or after the start date.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Check for overlapping leaves
        $overlappingLeave = Leave::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->first();

        if ($overlappingLeave) {
            return ApiResponseService::conflict();
        }

        // Generate unique slug
        $slug = Str::slug($user->name . '-' . $startDate->format('Y-m-d') . '-' . Str::random(6));
        while (Leave::where('slug', $slug)->exists()) {
            $slug = Str::slug($user->name . '-' . $startDate->format('Y-m-d') . '-' . Str::random(6));
        }

        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'reason' => $request->reason,
            'slug' => $slug,
            'status' => 'pending',
        ]);

        return ApiResponseService::created(
            responseKey: 'responses.created',
            data: new LeaveResource($leave)
        );
    }

    /**
     * Get a single leave by id
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $leave = Leave::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$leave) {
            return ApiResponseService::notFound();
        }

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: new LeaveResource($leave)
        );
    }
}
