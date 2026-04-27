<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Traits\HasResourcePermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use App\Traits\HasCustomSidebar;

class UserResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::User;
    protected static string|\UnitEnum|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 30;
    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return check_permission('users.view_any');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * Filter query based on permissions
     * If user has view_any, they see all users
     * If user has view or manage_own, they see only themselves
     */
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // Permission methods are now provided by HasResourcePermissions trait
    // canViewAny already includes manage_own in the trait, but we can keep this for clarity
    public static function canViewAny(): bool
    {
        $slug = static::getPermissionSlug();
        return check_permission(["{$slug}.view", "{$slug}.view_any", "{$slug}.manage_own"]);
    }

    public static function canCreate(): bool
    {
        $slug = static::getPermissionSlug();
        return check_permission(["{$slug}.create", "{$slug}.manage_own"]);
    }


    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record:slug}/view'),
            'edit' => EditUser::route('/{record:slug}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
    public static function afterSave($record, array $data): void
    {
        if (!empty($data['roles'])) {
            // If multiple = false, this is a string; else array
            $role = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
            $record->update(['role' => $role]);
        }
    }
}
