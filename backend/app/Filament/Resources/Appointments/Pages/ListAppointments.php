<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\ExternalBookings\ExternalBookingResource;
use App\Filament\Resources\ExternalBookings\Pages\ListExternalBookings;
use App\Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Schemas\Components\{
    Tabs\Tab
};
class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    public function getDefaultActiveTab(): string | int | null
    {
        $dateStr = data_get($this->tableFilters, 'appointment_date.date');
        if ($dateStr) {
            try {
                $date = \Carbon\Carbon::parse($dateStr)->startOfDay();
                $today = \Carbon\Carbon::today()->startOfDay();
                $tomorrow = \Carbon\Carbon::tomorrow()->startOfDay();

                if ($date->equalTo($today)) {
                    return 'today_opd';
                } elseif ($date->equalTo($tomorrow)) {
                    return 'tomorrow_opd';
                } elseif ($date->greaterThan($tomorrow)) {
                    return 'upcoming_opd';
                } else {
                    return 'all';
                }
            } catch (\Throwable $e) {
                // Return default
            }
        }

        return 'today_opd';
    }

    public function updatedTableFilters(): void
    {
        parent::updatedTableFilters();

        $dateStr = data_get($this->tableFilters, 'appointment_date.date');
        if ($dateStr) {
            try {
                $date = \Carbon\Carbon::parse($dateStr)->startOfDay();
                $today = \Carbon\Carbon::today()->startOfDay();
                $tomorrow = \Carbon\Carbon::tomorrow()->startOfDay();

                if ($date->equalTo($today)) {
                    $this->activeTab = 'today_opd';
                } elseif ($date->equalTo($tomorrow)) {
                    $this->activeTab = 'tomorrow_opd';
                } elseif ($date->greaterThan($tomorrow)) {
                    $this->activeTab = 'upcoming_opd';
                } else {
                    $this->activeTab = 'all';
                }
            } catch (\Throwable $e) {
                // Do nothing
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewExternalBookings')
                ->label('External Bookings')
                ->icon('heroicon-o-eye')
                ->url(fn () => ExternalBookingResource::getUrl('index'))
                ->visible(fn () => ExternalBookingResource::canViewAny()),
            ListExternalBookings::importExternalBookingsAction()
                ->visible(fn () => ExternalBookingResource::canCreate()),
        ];
    }

    public function hasActiveFilters(): bool
    {
        $filters = $this->tableFilters ?? [];

        // Check custom search filter
        $searchQuery = data_get($filters, 'search.query');
        if ($searchQuery !== null && $searchQuery !== '') {
            return true;
        }

        // Check OPD visit date filter
        $appointmentDate = data_get($filters, 'appointment_date.date');
        if ($appointmentDate !== null && $appointmentDate !== '') {
            return true;
        }

        // Check select filters
        foreach (['doctor_id', 'status', 'payment_status', 'admin_payment_type'] as $filterName) {
            $value = data_get($filters, "{$filterName}.value");
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    public function getTabs(): array
    {
        $resource = static::getResource();
        $confirmedOrRescheduledBookings = function (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder {
            return $query
                ->whereIn('status', [
                    \App\Enums\AppointmentStatus::CONFIRMED->value,
                    \App\Enums\AppointmentStatus::RESCHEDULED->value,
                ])
                ->where(function (\Illuminate\Database\Eloquent\Builder $query): void {
                    $query->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('status', \App\Enums\PaymentStatus::PAID->value))
                        ->orWhere(function (\Illuminate\Database\Eloquent\Builder $adminQuery): void {
                            $adminQuery
                                ->where('booking_source', 'admin')
                                ->where('admin_payment_type', 'without_payment');
                        });
                });
        };

        return [
            'today_opd' => Tab::make("Today's OPD")
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) use ($confirmedOrRescheduledBookings) {
                    if ($this->hasActiveFilters()) {
                        return $query;
                    }
                    return $confirmedOrRescheduledBookings($query->whereDate('appointment_date', \Carbon\Carbon::today()));
                })
                ->badge(fn () => $confirmedOrRescheduledBookings($resource::getEloquentQuery()->whereDate('appointment_date', \Carbon\Carbon::today()))->count())
                ->badgeColor('success'),

            'tomorrow_opd' => Tab::make("Tomorrow's OPD")
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) use ($confirmedOrRescheduledBookings) {
                    if ($this->hasActiveFilters()) {
                        return $query;
                    }
                    return $confirmedOrRescheduledBookings($query->whereDate('appointment_date', \Carbon\Carbon::tomorrow()));
                })
                ->badge(fn () => $confirmedOrRescheduledBookings($resource::getEloquentQuery()->whereDate('appointment_date', \Carbon\Carbon::tomorrow()))->count())
                ->badgeColor('success'),

            'upcoming_opd' => Tab::make("Upcoming OPD")
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) use ($confirmedOrRescheduledBookings) {
                    if ($this->hasActiveFilters()) {
                        return $query;
                    }
                    return $confirmedOrRescheduledBookings($query->whereDate('appointment_date', '>', \Carbon\Carbon::tomorrow()));
                })
                ->badge(fn () => $confirmedOrRescheduledBookings($resource::getEloquentQuery()->whereDate('appointment_date', '>', \Carbon\Carbon::tomorrow()))->count())
                ->badgeColor('success'),

            'all' => Tab::make('All Appointments')
                ->badge(fn () => $resource::getEloquentQuery()->count()),
        ];
    }
}
