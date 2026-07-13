<?php

namespace App\Filament\Pages;

use App\Enums\PaymentStatus;
use App\Models\{Appointment, Doctor, DoctorReview, Patient, Payment};
use App\Traits\HasCustomSidebar;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    use HasCustomSidebar;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Home;
    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-home';
    protected string $view = 'filament.pages.dashboard';
    protected static ?string $title = '';
    protected static ?string $navigationLabel = 'Dashboard';

    public static function getSidebarOptions(): array
    {
        return [
            'icon' => 'heroicon-o-home',
            'sort' => -10,
            'group' => null, // Top level
        ];
    }

    /**
     * Dashboard is accessible to all authenticated users
     */
    public static function canAccess(): bool
    {
        return Auth::check() && ! Auth::user()?->hasRole('patient');
    }

    /**
     * Check if user has dashboard permission to see cards
     */
    public function hasDashboardPermission(): bool
    {
        return check_permission('dashboard.view') || check_permission('dashboard.view_any');
    }

    /**
     * Get user's role name for display
     */
    public function getUserRole(): string
    {
        $user = Auth::user();
        if (!$user) {
            return 'Guest';
        }

        /** @var \App\Models\User $user */
        $role = $user->getPrimaryRole();
        return $role ? ucfirst(str_replace('_', ' ', $role)) : 'User';
    }

    /**
     * Check if user has any permissions at all
     */
    public function hasAnyPermissions(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        /** @var \App\Models\User $user */
        // Super admin always has permissions
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check if user has any permissions
        return $user->getAllPermissions()->count() > 0;
    }

    /**
     * Get dashboard analytics data for appointments and payments.
     */
    public function getDashboardAnalytics(): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(6)->startOfDay();
        $endDate = $now->copy()->endOfDay();

        $appointments = Appointment::query()
            ->select('appointment_date', 'consultation_type')
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($appointment) {
                $dateVal = $appointment->appointment_date;
                if (is_string($dateVal)) {
                    $dateVal = Carbon::parse($dateVal);
                }

                return $dateVal->format('Y-m-d');
            });

        $payments = Payment::query()
            ->select('amount', 'created_at', 'status')
            ->where('status', PaymentStatus::PAID)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($payment) {
                $createdAt = $payment->created_at;
                if (is_string($createdAt)) {
                    $createdAt = Carbon::parse($createdAt);
                }

                return $createdAt->format('Y-m-d');
            });

        $labels = [];
        $appointmentsAll = [];
        $appointmentsVideo = [];
        $appointmentsGeneral = [];
        $paymentsTotal = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateKey = $date->format('Y-m-d');

            $labels[] = $date->format('D');

            $dayAppointments = $appointments->get($dateKey, collect());
            $allCount = (int) $dayAppointments->count();
            $videoCount = (int) $dayAppointments
                ->filter(function ($appointment): bool {
                    $type = strtolower((string) ($appointment->consultation_type ?? ''));

                    return $type === 'video';
                })
                ->count();

            $generalCount = $allCount - $videoCount;

            $appointmentsAll[] = $allCount;
            $appointmentsVideo[] = $videoCount;
            $appointmentsGeneral[] = max(0, $generalCount);

            $dayPayments = $payments->get($dateKey, collect());
            $paymentsTotal[] = (float) $dayPayments->sum('amount');
        }

        // Monthly data - last 12 months
        $monthStart = $now->copy()->subMonths(11)->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $monthlyAppointmentsQuery = Appointment::query()
            ->select('appointment_date', 'consultation_type')
            ->whereBetween('appointment_date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(function ($appointment) {
                $dateVal = $appointment->appointment_date;
                if (is_string($dateVal)) {
                    $dateVal = Carbon::parse($dateVal);
                }

                return $dateVal->format('Y-m');
            });

        $monthlyPaymentsQuery = Payment::query()
            ->select('amount', 'created_at', 'status')
            ->where('status', PaymentStatus::PAID)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(function ($payment) {
                $createdAt = $payment->created_at;
                if (is_string($createdAt)) {
                    $createdAt = Carbon::parse($createdAt);
                }

                return $createdAt->format('Y-m');
            });

        $monthLabels = [];
        $monthAppointmentsAll = [];
        $monthAppointmentsVideo = [];
        $monthAppointmentsGeneral = [];
        $monthPaymentsTotal = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthKey = $date->format('Y-m');

            $monthLabels[] = $date->format('M');

            $monthAppointments = $monthlyAppointmentsQuery->get($monthKey, collect());
            $monthAllCount = (int) $monthAppointments->count();
            $monthVideoCount = (int) $monthAppointments
                ->filter(function ($appointment): bool {
                    $type = strtolower((string) ($appointment->consultation_type ?? ''));

                    return $type === 'video';
                })
                ->count();

            $monthGeneralCount = $monthAllCount - $monthVideoCount;

            $monthAppointmentsAll[] = $monthAllCount;
            $monthAppointmentsVideo[] = $monthVideoCount;
            $monthAppointmentsGeneral[] = max(0, $monthGeneralCount);

            $monthPayments = $monthlyPaymentsQuery->get($monthKey, collect());
            $monthPaymentsTotal[] = (float) $monthPayments->sum('amount');
        }

        return [
            'meta' => [
                'current_month' => $now->format('F Y'),
            ],
            'week' => [
                'labels' => $labels,
                'appointments' => [
                    'all' => $appointmentsAll,
                    'video' => $appointmentsVideo,
                    'general' => $appointmentsGeneral,
                ],
                'payments' => [
                    'total' => $paymentsTotal,
                ],
            ],
            'month' => [
                'labels' => $monthLabels,
                'appointments' => [
                    'all' => $monthAppointmentsAll,
                    'video' => $monthAppointmentsVideo,
                    'general' => $monthAppointmentsGeneral,
                ],
                'payments' => [
                    'total' => $monthPaymentsTotal,
                ],
            ],
        ];
    }

    /**
     * Get high-level summary metrics for top dashboard cards.
     */
    public function getDashboardSummary(): array
    {
        $today = Carbon::today();

        $totalPatients = Patient::visibleTo()->count();

        $appointmentsToday = Appointment::visibleTo()
            ->whereDate('appointment_date', $today)
            ->count();

        $activeDoctors = Doctor::visibleTo()
            ->where('status', 'active')
            ->count();

        $pendingReviews = DoctorReview::query()
            ->where('is_active', false)
            ->count();

        return [
            'total_patients' => $totalPatients,
            'appointments_today' => $appointmentsToday,
            'active_doctors' => $activeDoctors,
            'pending_reviews' => $pendingReviews,
        ];
    }
}
