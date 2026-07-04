<?php

namespace App\Livewire;

use App\Services\OpdToken\DisplayAuthService;
use App\Services\OpdToken\DisplayBoardService;
use App\Services\OpdToken\DoctorScheduleBoardService;
use App\Services\OpdToken\DisplaySettingsService;
use Illuminate\View\View;
use Livewire\Component;

class OptTokenDisplay extends Component
{
    public string $password = '';

    public bool $authenticated = false;

    public array $display = [];

    public array $board = [];

    protected function settingsService(): DisplaySettingsService
    {
        return app(DisplaySettingsService::class);
    }

    protected function authService(): DisplayAuthService
    {
        return app(DisplayAuthService::class);
    }

    protected function boardService(): DisplayBoardService
    {
        return app(DisplayBoardService::class);
    }

    protected function scheduleBoardService(): DoctorScheduleBoardService
    {
        return app(DoctorScheduleBoardService::class);
    }

    public function mount(): void
    {
        $this->display = $this->settingsService()->load();
        $this->authenticated = $this->authService()->isAuthenticated(request(), $this->display);
        $this->board = $this->resolveBoard();
    }

    public function authenticate(): void
    {
        $password = trim($this->password);

        if (! $this->authService()->authenticate(request(), $this->display, $password)) {
            $this->addError('password', 'Invalid display password.');

            return;
        }

        $this->authenticated = true;
        $this->password = '';
        $this->resetErrorBag('password');
        $this->refreshBoard();
    }

    public function logoutDisplay(): void
    {
        $this->authService()->forget(request(), $this->display);
        $this->authenticated = false;
        $this->refreshBoard();
    }

    public function refreshBoard(): void
    {
        $this->display = $this->settingsService()->load();
        $this->authenticated = $this->authService()->isAuthenticated(request(), $this->display);
        $this->board = $this->resolveBoard();
    }

    protected function resolveBoard(): array
    {
        if (($this->display['display_mode'] ?? 'auto') === 'doctor_schedule_sidebar') {
            return $this->scheduleBoardService()->buildBoard($this->display);
        }

        return $this->boardService()->buildBoard($this->display);
    }

    public function render(): View
    {
        return view('livewire.opt-token-display');
    }
}
