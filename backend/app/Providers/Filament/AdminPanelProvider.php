<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login as CustomLogin;
use Filament\Enums\DatabaseNotificationsPosition;
use App\Filament\Pages\{AppointmentQueueDashboard, BookAppointment, CronSettings, Dashboard, DisplayAdsSettings, DisplayScreenSettings, DoctorReport, ManageVideoLinks, OPDCalendar, PrescriptionVoiceSettings, QueueLogsDashboard, RolePermissionMatrix, Settings};
use App\Filament\Resources\DisplayScreens\DisplayScreenResource;
use App\Filament\Resources\Advertisements\AdvertisementResource;
use App\Filament\Resources\EmailLogs\EmailLogResource;
use App\Filament\Resources\DoctorAdvertisements\DoctorAdvertisementResource;
use App\Filament\Resources\PrescriptionDrafts\PrescriptionDraftResource;
use App\Filament\Resources\{Appointments\AppointmentResource, DoctorDepartments\DoctorDepartmentResource, DoctorReplacements\DoctorReplacementResource, DoctorReviews\DoctorReviewResource, Doctors\DoctorAvailabilityResource, Doctors\DoctorResource, ContactUs\ContactUsResource, ExternalBookings\ExternalBookingResource, Leaves\LeaveResource, MedicalReports\MedicalReportResource, Medicines\MedicineResource, ModuleDocuments\ModuleDocumentResource, Patients\PatientResource, Payments\PaymentResource, Symptoms\SymptomResource, Users\UserResource, Vendors\VendorResource};
use App\Filament\Resources\DietTemplates\DietTemplateResource;
use App\Filament\Resources\MedicineTemplates\MedicineTemplateResource;
use App\Filament\Resources\PatientDietPlans\PatientDietPlanResource;
use App\Filament\Resources\PatientVaccinations\PatientVaccinationResource;
use App\Filament\Resources\VaccinationClinicalInsights\VaccinationClinicalInsightResource;
use App\Filament\Resources\VaccinationDocuments\VaccinationDocumentResource;
use App\Filament\Resources\VaccinationGeneralFaqs\VaccinationGeneralFaqResource;
use App\Filament\Resources\VaccinationTemplates\VaccinationTemplateResource;
use App\Filament\Resources\Vaccinations\VaccinationResource;
use App\Filament\Resources\CustomCronJobResource;
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
            ->passwordReset()
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
                PrescriptionVoiceSettings::class,
                CronSettings::class,
                DisplayScreenSettings::class,
                DisplayAdsSettings::class,
                BookAppointment::class,
                DoctorReport::class,
                ManageVideoLinks::class,
                AppointmentQueueDashboard::class,
                QueueLogsDashboard::class,
            ])
            ->resources([
                AppointmentResource::class,
                ExternalBookingResource::class,
                PatientResource::class,
                DoctorResource::class,
                DoctorAvailabilityResource::class,
                ContactUsResource::class,
                DoctorDepartmentResource::class,
                MedicineResource::class,
                MedicineTemplateResource::class,
                SymptomResource::class,
                LeaveResource::class,
                VendorResource::class,
                UserResource::class,
                DisplayScreenResource::class,
                AdvertisementResource::class,
                DoctorAdvertisementResource::class,
                DoctorReviewResource::class,
                DoctorReplacementResource::class,
                PaymentResource::class,
                MedicalReportResource::class,
                ModuleDocumentResource::class,
                EmailLogResource::class,
                PrescriptionDraftResource::class,
                DietTemplateResource::class,
                PatientDietPlanResource::class,
                PatientVaccinationResource::class,
                VaccinationClinicalInsightResource::class,
                VaccinationDocumentResource::class,
                VaccinationGeneralFaqResource::class,
                VaccinationTemplateResource::class,
                VaccinationResource::class,
                CustomCronJobResource::class,
            ])
            ->navigation(fn(NavigationBuilder $builder): NavigationBuilder => \App\Filament\CustomSidebarManager::buildFilamentNavigation($builder))
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->profile(isSimple: false)
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
                PanelsRenderHook::BODY_END,
                fn(): string => view('filament.partial.webpush-loader')->render(),
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
            return Setting::getValue('app', 'primary_color', '#055bd9') ?? '#055bd9';
        } catch (\Exception $e) {
            return '#055bd9';
        }
    }

    protected function getSecondaryColor(): string
    {
        try {
            return Setting::getValue('app', 'secondary_color', '#055bd9') ?? '#055bd9';
        } catch (\Exception $e) {
            return '#055bd9';
        }
    }

    protected function getBrandLogo(): \Illuminate\Contracts\View\View
    {
        // Default Assets
        $logo = asset('images/white-logo.png');
        $black_logo = asset('images/deploymeta.png');
        $icon = asset('images/deploymeta.png');

        try {
            // Check for dynamic logo from Settings
            $settingLogo = Setting::getValue('app', 'logo');
            if ($settingLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($settingLogo)) {
                $logo = storage_url($settingLogo);
            }
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
        return asset('images/fav-icon.png');
    }

    protected function getAppName(): string
    {
        try {
            return Setting::getValue('app', 'name', config('app.name', 'Telehealth Deploymeta')) ?? 'Telehealth Deploymeta';
        } catch (\Exception $e) {
            return config('app.name', 'Telehealth Deploymeta');
        }
    }
}