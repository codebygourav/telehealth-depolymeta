<?php

namespace App\Helpers;

use Filament\Tables\Columns\TextColumn;

if (!function_exists('getUserAuditColumn')) {

    function getUserAuditColumn(string $relationship, string $label): TextColumn
    {
        return TextColumn::make("{$relationship}.name")
            ->label($label)
            ->formatStateUsing(function ($state, $record) use ($relationship) {
                try {
                    $user = $record->{$relationship};
                    if (!$user) {
                        return 'Seeder';
                    }

                    $name = $user->name ?? 'Seeder';
                    $role = $user->role ? ucwords(str_replace('_', ' ', $user->role)) : '';

                    return $role ? "{$name} ({$role})" : $name;
                } catch (\Exception $e) {
                    return 'Seeder';
                }
            })
            ->searchable(query: function ($query, $search) use ($relationship) {
                return $query->whereHas($relationship, function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}