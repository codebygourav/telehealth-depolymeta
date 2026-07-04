<?php

namespace App\Http\Controllers;

use App\Models\DisplayScreen;
use App\Services\OpdToken\DisplayAuthService;
use App\Services\OpdToken\DisplayBoardService;
use App\Services\OpdToken\DoctorScheduleBoardService;
use App\Services\OpdToken\DisplaySettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DisplayTokenController extends Controller
{
    public function __construct(
        protected DisplaySettingsService $settingsService,
        protected DisplayAuthService $authService,
        protected DisplayBoardService $boardService,
        protected DoctorScheduleBoardService $scheduleBoardService,
    ) {
    }

    public function show(Request $request, ?DisplayScreen $screen = null): View
    {
        abort_if($screen && ! $screen->is_active, 404);

        $display = $this->settingsService->load($screen);
        $authenticated = $this->authService->isAuthenticated($request, $display);
        $board = $this->resolveBoard($display);

        return view('livewire.opt-token-display', [
            'authenticated' => $authenticated,
            'board' => $board,
            'display' => $display,
            'displayScreen' => $screen,
            'authenticateAction' => $this->authenticateRoute($screen),
            'passwordError' => session('display_auth_error'),
        ]);
    }

    public function boardData(Request $request, ?DisplayScreen $screen = null): JsonResponse
    {
        abort_if($screen && ! $screen->is_active, 404);

        $display = $this->settingsService->load($screen);

        if (! $this->authService->isAuthenticated($request, $display)) {
            return response()->json([
                'message' => 'Unauthenticated display access.',
            ], 403);
        }

        return response()->json($this->resolveBoard($display));
    }

    public function authenticate(Request $request, ?DisplayScreen $screen = null): RedirectResponse
    {
        abort_if($screen && ! $screen->is_active, 404);

        $display = $this->settingsService->load($screen);
        $password = trim((string) $request->input('password'));

        if ($this->authService->authenticate($request, $display, $password)) {
            return redirect()->to($this->displayRoute($screen));
        }

        return redirect()
            ->to($this->displayRoute($screen))
            ->with('display_auth_error', 'Invalid display password.');
    }

    public function logout(Request $request, ?DisplayScreen $screen = null): RedirectResponse
    {
        abort_if($screen && ! $screen->is_active, 404);

        $display = $this->settingsService->load($screen);
        $this->authService->forget($request, $display);

        return redirect()->to($this->displayRoute($screen));
    }

    protected function resolveBoard(array $display): array
    {
        if (($display['display_mode'] ?? 'auto') === 'doctor_schedule_sidebar') {
            return $this->scheduleBoardService->buildBoard($display);
        }

        return $this->boardService->buildBoard($display);
    }

    protected function displayRoute(?DisplayScreen $screen = null): string
    {
        return $screen
            ? route('opd-token.screen.display', ['screen' => $screen])
            : route('opd-token.display');
    }

    protected function authenticateRoute(?DisplayScreen $screen = null): string
    {
        return $screen
            ? route('opd-token.screen.authenticate', ['screen' => $screen])
            : route('opd-token.authenticate');
    }
}
