<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Doctor\UsageAnalyticsResource;
use App\Services\{ApiResponseService, AppointmentAnalyticsService};
use Illuminate\Http\Request;

class UsageAnalyticsController extends Controller
{
    protected AppointmentAnalyticsService $analyticsService;

    public function __construct(AppointmentAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get usage analytics for doctor
     * Returns appointment statistics with comparison and chart data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->doctor) {
            return ApiResponseService::notFound();
        }

        $doctorId = $user->doctor->id;

        // Month comparison
        $comparison = $this->analyticsService->getMonthComparison($doctorId);

        // All chart data
        $chartData = [
            'week' => $this->analyticsService->getWeeklyChartData($doctorId),
            'month' => $this->analyticsService->getMonthlyChartData($doctorId),
            'year' => $this->analyticsService->getYearlyChartData($doctorId),
        ];

        $data = [
            'summary' => [
                'current_month_count' => $comparison['current_month_count'],
                'percentage_change' => $comparison['percentage_change'],
                'is_positive' => $comparison['is_positive'],
                'last_month_count' => $comparison['last_month_count'],
            ],
            'chart_data' => $chartData,
        ];

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: new UsageAnalyticsResource($data)
        );
    }
}
