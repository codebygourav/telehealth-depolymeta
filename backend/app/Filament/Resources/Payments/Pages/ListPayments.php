<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use Carbon\Carbon;
use App\Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }

    public function getTabs(): array
    {
        $resource = static::getResource();

        return [
            'today' => Tab::make("Today's Payments")
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', Carbon::today()))
                ->badge(fn() => $resource::getEloquentQuery()->whereDate('created_at', Carbon::today())->count())
                ->badgeColor('success'),

            'pending' => Tab::make('Pending Payments')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', PaymentStatus::PENDING->value))
                ->badge(fn() => $resource::getEloquentQuery()->where('status', PaymentStatus::PENDING->value)->count())
                ->badgeColor('warning'),

            'failed' => Tab::make('Failed Payments')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', PaymentStatus::FAILED->value))
                ->badge(fn() => $resource::getEloquentQuery()->where('status', PaymentStatus::FAILED->value)->count())
                ->badgeColor('danger'),

            'refunded' => Tab::make('Refunded Payments')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', PaymentStatus::REFUNDED->value))
                ->badge(fn() => $resource::getEloquentQuery()->where('status', PaymentStatus::REFUNDED->value)->count())
                ->badgeColor('gray'),

            'all' => Tab::make('All Payments')
                ->badge(fn() => $resource::getEloquentQuery()->count()),
        ];
    }
}
