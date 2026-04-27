<?php

namespace App\Filament\Resources\ContactUs;

use App\Filament\Resources\ContactUs\Pages\CreateContactUs;
use App\Filament\Resources\ContactUs\Pages\EditContactUs;
use App\Filament\Resources\ContactUs\Pages\ListContactUs;
use App\Filament\Resources\ContactUs\Pages\ViewContactUs;
use App\Filament\Resources\ContactUs\Schemas\ContactUsForm;
use App\Filament\Resources\ContactUs\Schemas\ContactUsInfolist;
use App\Filament\Resources\ContactUs\Tables\ContactUsTable;
use App\Models\ContactUs;
use BackedEnum;
use App\Traits\HasCustomSidebar;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;


class ContactUsResource extends Resource
{
    use HasCustomSidebar;

    protected static ?string $model = ContactUs::class;

    public static function getSidebarOptions(): array
    {
        return [
            'icon'  => 'heroicon-o-envelope',
            'sort'  => 4,
            'group' => null, // Standalone menu
        ];
    }

    protected static ?string $recordTitleAttribute = 'Inquiry';
    protected static ?string $title = 'Inquiries';
    protected static ?string $navigationLabel = 'Inquiries';
    protected static ?string $slug = 'inquiries';



    public static function getModelLabel(): string //this function help to update the breadcrumb title for index screen
    {
        return 'Contact Submission';
    }

    public static function canViewAny(): bool
    {
        return check_permission(['inquiries.view_any', 'inquiries.view']);
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return check_permission(['inquiries.view_any', 'inquiries.view']);
    }

    public static function canCreate(): bool
    {
        return check_permission('inquiries.create');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return check_permission(['inquiries.update', 'inquiries.edit']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return check_permission('inquiries.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return ContactUsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactUsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactUsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactUs::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }
}
