<?php

namespace App\Http\Controllers;

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

    public function show(Request $request): View
    {
        $display = $this->settingsService->load();
        $authenticated = $this->authService->isAuthenticated($request, $display);
        $board = $this->resolveBoard($display);

        return view('livewire.opt-token-display', [
            'authenticated' => $authenticated,
            'board' => $board,
            'display' => $display,
            'passwordError' => session('display_auth_error'),
        ]);
    }

    public function boardData(Request $request): JsonResponse
    {
        $display = $this->settingsService->load();

        if (! $this->authService->isAuthenticated($request, $display)) {
            return response()->json([
                'message' => 'Unauthenticated display access.',
            ], 403);
        }

        return response()->json($this->resolveBoard($display));
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $display = $this->settingsService->load();
        $password = trim((string) $request->input('password'));

        if ($this->authService->authenticate($request, $display, $password)) {
            return redirect()->route('opd-token.display');
        }

        return redirect()
            ->route('opd-token.display')
            ->with('display_auth_error', 'Invalid display password.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authService->forget($request);

        return redirect()->route('opd-token.display');
    }

    protected function resolveBoard(array $display): array
    {
        if (($display['display_mode'] ?? 'auto') === 'doctor_schedule_sidebar') {
            return $this->scheduleBoardService->buildBoard($display);
        }

        return $this->boardService->buildBoard($display);
    }
}
