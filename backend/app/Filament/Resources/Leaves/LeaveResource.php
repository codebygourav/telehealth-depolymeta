<?php

namespace App\Filament\Resources\Leaves;

use App\Filament\Resources\Leaves\Pages\CreateLeave;
use App\Filament\Resources\Leaves\Pages\EditLeave;
use App\Filament\Resources\Leaves\Pages\ListLeaves;
use App\Filament\Resources\Leaves\Pages\ViewLeave;
use App\Filament\Resources\Leaves\Schemas\LeaveForm;
use App\Filament\Resources\Leaves\Schemas\LeaveInfolist;
use App\Filament\Resources\Leaves\Tables\LeavesTable;
use App\Models\Leave;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = Leave::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $recordTitleAttribute = 'Leave';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Leaves',
            'icon'  => 'heroicon-o-calendar',
            'sort'  => 4,
            'group' => '',
        ];
    }
    // Permission methods are now provided by HasResourcePermissions trait

    public static function form(Schema $schema): Schema
    {
        return LeaveForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeavesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        // Since canAccess does not exist, restrict available pages based on permissions here
        $pages = [
            'index' => ListLeaves::route('/'),
        ];

        // if (static::canCreate()) {
        $pages['create'] = CreateLeave::route('/create');
        // }

        // if (static::canViewAny()) {
        $pages['view'] = ViewLeave::route('/{record:slug}');
        // }

        // For edit page, you typically need to check edit-permission per record at action level,
        // but here we only restrict registration of the route if editing is allowed in general.
        // if (static::canEdit(null)) {
        $pages['edit'] = EditLeave::route('/{record}/edit');
        // }

        return $pages;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name',
            ]);

        // Apply permission-based filtering
        return static::filterQueryByOwnership($query);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
