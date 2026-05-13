<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login as CustomLogin;
use Filament\Enums\DatabaseNotificationsPosition;
use App\Filament\Pages\{Dashboard, DoctorReport, OPDCalendar, RolePermissionMatrix, Settings, TestRazorpayBooking, TestVideoConsultation};
use App\Filament\Resources\Advertisements\AdvertisementResource;
use App\Filament\Resources\{Appointments\AppointmentResource, DoctorDepartments\DoctorDepartmentResource, DoctorReplacements\DoctorReplacementResource, DoctorReviews\DoctorReviewResource, Doctors\DoctorResource, ContactUs\ContactUsResource, Leaves\LeaveResource, MedicalReports\MedicalReportResource, Medicines\MedicineResource, ModuleDocuments\ModuleDocumentResource, Patients\PatientResource, Payments\PaymentResource, Symptoms\SymptomResource, Users\UserResource, Vendors\VendorResource};
use App\Filament\Resources\Vaccinations\VaccinationResource;
use App\Filament\Resources\VaccinationTemplates\VaccinationTemplateResource;
use App\Filament\Resources\PatientVaccinations\PatientVaccinationResource;
use App\Models\Setting;
use Filament\Http\Middleware\{Authenticate, AuthenticateSession, DisableBladeIconComponents, DispatchServingFilamentEvent};
use Filament\Navigation\NavigationBuilder;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\{Panel, PanelProvider, View\PanelsRenderHook, Widgets};
use Illuminate\Cookie\Middleware\{AddQueuedCookiesToResponse, EncryptCookies};
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->login(CustomLogin::class)
            ->colors([
                'primary' => $this->getPrimaryColor(),
                'success' => $this->getSecondaryColor(),
                'danger' => '#ef4444',
                'info' => '#3b82f6',
                'warning' => '#f59e0b',
            ])
            ->globalSearch(false)
            ->brandName($this->getAppName())
            ->brandLogo(fn() => $this->getBrandLogo())
            ->brandLogoHeight('2.3rem')
            ->favicon(fn() => $this->getFavicon())
            ->sidebarWidth('15rem')
            ->subNavigationPosition(SubNavigationPosition::Start)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(condition: false)
            ->pages([
                Dashboard::class,
                OPDCalendar::class,
                RolePermissionMatrix::class,
                Settings::class,
                TestRazorpayBooking::class,
                TestVideoConsultation::class,
                TestRazorpayBooking::class,
                DoctorReport::class,
            ])
            ->resources([
                AppointmentResource::class,
                PatientResource::class,
                DoctorResource::class,
                ContactUsResource::class,
                DoctorDepartmentResource::class,
                MedicineResource::class,
                SymptomResource::class,
                LeaveResource::class,
                VendorResource::class,
                UserResource::class,
                AdvertisementResource::class,
                DoctorReviewResource::class,
                DoctorReplacementResource::class,
                PaymentResource::class,
                MedicalReportResource::class,
                ModuleDocumentResource::class,
                VaccinationResource::class,
                VaccinationTemplateResource::class,
                PatientVaccinationResource::class,
            ])
            ->navigation(fn(NavigationBuilder $builder): NavigationBuilder => \App\Filament\CustomSidebarManager::buildFilamentNavigation($builder))
            ->unsavedChangesAlerts()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->profile(isSimple: true)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => view('filament.partial.theme-colors')->render(),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn(): string => view('filament.partial.footer')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn(): string => view('filament.components.sidebar-user-menu')->render(),
            )
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->unsavedChangesAlerts()
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public static function isGloballySearchable(): bool
    {
        return false;
    }

    protected function getPrimaryColor(): string
    {
        try {
            return Setting::getValue('app', 'primary_color', '#073827') ?? '#073827';
        } catch (\Exception $e) {
            return '#073827';
        }
    }

    protected function getSecondaryColor(): string
    {
        try {
            return Setting::getValue('app', 'secondary_color', '#22c55e') ?? '#22c55e';
        } catch (\Exception $e) {
            return '#22c55e';
        }
    }

    protected function getBrandLogo(): \Illuminate\Contracts\View\View
    {
        // Default Assets
        $logo = asset('images/cmc-telehealth.png');
        $icon = asset('images/cmc-telehealth.png');

        try {
            // Check for dynamic logo from Settings
            $settingLogo = Setting::getValue('app', 'logo');
            if ($settingLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($settingLogo)) {
                $logo = storage_url($settingLogo);
            }

            // Check for dynamic icon/favicon from Settings
            // Note: The user mentioned "flateicon" or "favicon" in settings.
            // We use this for the collapsed icon as well to prefer the user's uploaded icon if available.
            $settingFavicon = Setting::getValue('app', 'favicon');
            if ($settingFavicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($settingFavicon)) {
                $icon = storage_url($settingFavicon);
            }
        } catch (\Exception $e) {
            // Fallback to default if DB or Storage fails
        }

        return view('filament.components.brand-logo', [
            'logo' => $logo,
            'icon' => $icon,
        ]);
    }

    protected function getFavicon(): ?string
    {
        try {
            $favicon = Setting::getValue('app', 'favicon');
            if ($favicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($favicon)) {
                return storage_url($favicon);
            }
        } catch (\Exception $e) {
            // Fallback to default
        }

        return asset('favicon.ico');
    }

    protected function getAppName(): string
    {
        try {
            return Setting::getValue('app', 'name', config('app.name', 'CMC Telehealth')) ?? 'CMC Telehealth';
        } catch (\Exception $e) {
            return config('app.name', 'CMC Telehealth');
        }
    }
}
