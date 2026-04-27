<?php

namespace App\Filament\Resources\DoctorReviews;

use App\Filament\Resources\DoctorReviews\Pages\CreateDoctorReview;
use App\Filament\Resources\DoctorReviews\Pages\EditDoctorReview;
use App\Filament\Resources\DoctorReviews\Pages\ListDoctorReviews;
use App\Filament\Resources\DoctorReviews\Pages\ViewDoctorReview;
use App\Filament\Resources\DoctorReviews\Schemas\DoctorReviewForm;
use App\Filament\Resources\DoctorReviews\Schemas\DoctorReviewInfolist;
use App\Filament\Resources\DoctorReviews\Tables\DoctorReviewsTable;
use App\Models\DoctorReview;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasCustomSidebar;

class DoctorReviewResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = DoctorReview::class;
    protected static ?string $slug = 'feedback';
    protected static ?string $navigationLabel = 'Feedback';
    protected static ?string $modelLabel = 'Doctor Review';
    protected static string|\UnitEnum|null $navigationGroup = 'Doctor Management';
    protected static ?int $navigationSort = 13;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return DoctorReviewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DoctorReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DoctorReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctorReviews::route('/'),
            'create' => CreateDoctorReview::route('/create'),
            'view' => ViewDoctorReview::route('/{record:slug}'),
            'edit' => EditDoctorReview::route('/{record:slug}/edit'),
        ];
    }

    // Permission methods are now provided by HasResourcePermissions trait

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
                'patient:id,user_id,first_name,last_name',
                'patient.user:id,name', // avatar is accessed via InteractsWithModuleDocuments trait
                'fakerPatient:id,name,age,address',
                'doctor:id,user_id,first_name,last_name',
                'doctor.user:id,name',
                'appointment:id,appointment_date,appointment_time,status,consultation_type',
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}