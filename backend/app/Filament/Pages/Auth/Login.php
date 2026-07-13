<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected string $view = 'filament.auth.login';

    public function hasLogo(): bool
    {
        return false;
    }

    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response) {
            $user = Filament::auth()->user();
            $userName = $user?->name;

            if ($user?->hasRole('patient')) {
                session()->put('url.intended', PatientResource::getUrl());
            }

            Notification::make()
                ->success()
                ->title(__('Welcome back!'))
                ->body(__('You are now logged into the admin panel.') . ($userName ? ' ' . __('Have a great day, :name!', ['name' => $userName]) : ''))
                ->send();
        }

        return $response;
    }
}
