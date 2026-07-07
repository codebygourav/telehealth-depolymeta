<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorResource;
use App\Filament\Imports\DoctorExcelImporter;
use App\Models\Doctor;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class ListDoctors extends ListRecords
{
    protected static string $resource = DoctorResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $canManage = $user?->hasRole('super_admin') || $user?->can('doctors.create');
        $canView = $user?->hasRole('super_admin') || $user?->can('doctors.view') || $user?->can('doctors.view_any');

        return [
            // Import/Export Group
            ActionGroup::make([
                Action::make('importDoctors')
                    ->label('Import from CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->modalHeading('Import Doctors')
                    ->modalDescription('Upload a CSV file to import doctor profiles. Download the template first if you need the correct format.')
                    ->modalIcon('heroicon-o-arrow-up-tray')
                    ->modalSubmitActionLabel('Import')
                    ->form([
                        FileUpload::make('file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', '.csv'])
                            ->disk('local')
                            ->directory('imports')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $filePath = Storage::disk('local')->path($data['file']);
                        $importer = new DoctorExcelImporter();
                        $results = $importer->import($filePath);
                        Storage::disk('local')->delete($data['file']);

                        if ($results['successful'] > 0) {
                            Notification::make()
                                ->title('Import Completed')
                                ->body("Successfully imported {$results['successful']} doctor(s).")
                                ->success()
                                ->send();
                        }

                        if ($results['skipped'] > 0) {
                            Notification::make()
                                ->title('Skipped Records')
                                ->body("{$results['skipped']} doctor(s) already exist.")
                                ->info()
                                ->send();
                        }

                        if ($results['failed'] > 0) {
                            Notification::make()
                                ->title('Import Errors')
                                ->body("{$results['failed']} doctor(s) failed.")
                                ->warning()
                                ->send();
                        }

                        foreach (array_slice($results['errors'], 0, 3) as $error) {
                            Notification::make()
                                ->title('Error')
                                ->body($error)
                                ->danger()
                                ->duration(8000)
                                ->send();
                        }

                        if ($results['successful'] === 0 && $results['failed'] === 0 && $results['skipped'] === 0) {
                            Notification::make()
                                ->title('No Data Found')
                                ->body('No valid records found.')
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn() => $canManage),

                Action::make('exportDoctors')
                    ->label('Export to CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn() => $this->exportDoctors())
                    ->visible(fn() => $canView),

                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(fn() => $this->downloadTemplate())
                    ->visible(fn() => $canManage),
            ])
                ->label('Import / Export')
                ->icon('heroicon-o-arrow-path')
                ->button()
                ->visible(fn() => $canManage || $canView),

            // Create Doctor
            CreateAction::make()
                ->label('New Doctor')
                ->icon('heroicon-o-plus')
                ->visible(fn() => $canManage),
        ];
    }

    /**
     * Download template with sample data
     */
    protected function downloadTemplate()
    {
        $fields = $this->getFieldsConfig();

        return response()->streamDownload(function () use ($fields) {
            $handle = fopen('php://output', 'w');

            foreach ($fields as $field) {
                fputcsv($handle, [$field['label'] . ':', $field['sample'], '', '']);
            }

            fclose($handle);
        }, 'doctor_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Export all doctors to CSV
     */
    protected function exportDoctors()
    {
        $doctors = Doctor::with(['user', 'departments', 'availabilities'])->get();
        $fields = $this->getFieldsConfig();

        return response()->streamDownload(function () use ($doctors, $fields) {
            $handle = fopen('php://output', 'w');

            foreach ($fields as $field) {
                $row = [$field['label'] . ':'];

                foreach ($doctors as $doctor) {
                    $row[] = $this->getFieldValue($doctor, $field['key']);
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'doctors_export_' . date('Y-m-d_His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Get field value from doctor record
     */
    protected function getFieldValue(Doctor $doctor, string $key): string
    {
        switch ($key) {
            // User fields
            case 'full_name':
                return trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
            case 'email':
                return $doctor->user?->email ?? '';
            case 'phone':
                return $doctor->user?->phone ?? '';

                // Enums
            case 'gender':
            case 'marital_status':
            case 'status':
                $val = $doctor->{$key};
                return is_object($val) ? ($val->value ?? '') : ($val ?? '');

                // Languages (JSON array to comma-separated)
            case 'languages_known':
                $langs = $doctor->languages_known;
                return is_array($langs) ? implode(', ', $langs) : ($langs ?? '');

                // Education Info (JSON Repeater)
            case 'education_degree':
                return $this->getJsonField($doctor->education_info, 'degree');
            case 'education_institution':
                return $this->getJsonField($doctor->education_info, 'institution');
            case 'education_completion_year':
                return $this->getJsonField($doctor->education_info, 'completion_year');

                // Awards Info (JSON Repeater)
            case 'award_title':
                return $this->getJsonField($doctor->awards_info, 'title');
            case 'award_year':
                return $this->getJsonField($doctor->awards_info, 'year');
            case 'award_description':
                return $this->getJsonField($doctor->awards_info, 'description');

                // Certifications Info (JSON Repeater)
            case 'certification_name':
                return $this->getJsonField($doctor->certifications_info, 'name');
            case 'certification_organization':
                return $this->getJsonField($doctor->certifications_info, 'organization');
            case 'certification_description':
                return $this->getJsonField($doctor->certifications_info, 'description');

                // Professional Experience (JSON Repeater)
            case 'association':
                return $this->getJsonField($doctor->professional_experience_info, 'association');
            case 'experience_description':
                return $this->getJsonField($doctor->professional_experience_info, 'description');

                // Fellowships Info (JSON Repeater)
            case 'fellowship_title':
                return $this->getJsonField($doctor->fellowships_info, 'title');
            case 'fellowship_institution':
                return $this->getJsonField($doctor->fellowships_info, 'institution');
            case 'fellowship_year_started':
                return $this->getJsonField($doctor->fellowships_info, 'year_started');
            case 'fellowship_description':
                return $this->getJsonField($doctor->fellowships_info, 'description');

                // Social Links (JSON Object)
            case 'social_facebook':
                return $doctor->social_links['facebook'] ?? '';
            case 'social_twitter':
                return $doctor->social_links['twitter'] ?? '';
            case 'social_linkedin':
                return $doctor->social_links['linkedin'] ?? '';
            case 'social_instagram':
                return $doctor->social_links['instagram'] ?? '';
            case 'social_website':
                return $doctor->social_links['website'] ?? '';

                // Department
            case 'department':
                return $doctor->departments->pluck('name')->implode(', ');
            case 'department_role':
                return $doctor->departments->map(fn($d) => $d->pivot->role ?? '')->filter()->implode(', ');

                // Avatar
            case 'avatar':
                $avatarPath = $doctor->avatar ?? $doctor->user?->avatar;
                return $avatarPath ? storage_url($avatarPath) : '';

                // Simple fields
            default:
                return $doctor->{$key} ?? '';
        }
    }

    /**
     * Get first item's field from JSON array
     */
    protected function getJsonField($data, string $key): string
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return $data[0][$key] ?? '';
    }

    /**
     * Field configuration matching DoctorForm.php exactly
     */
    protected function getFieldsConfig(): array
    {
        return [
            // ==================== USER TABLE ====================
            ['label' => 'Full Name', 'key' => 'full_name', 'sample' => 'John Smith'],
            ['label' => 'Email', 'key' => 'email', 'sample' => 'john.smith@hospital.com'],
            ['label' => 'Phone', 'key' => 'phone', 'sample' => '+91 9876543210'],

            // ==================== BASIC INFORMATION ====================
            ['label' => 'First Name', 'key' => 'first_name', 'sample' => 'John'],
            ['label' => 'Last Name', 'key' => 'last_name', 'sample' => 'Smith'],
            ['label' => 'Date of Birth', 'key' => 'dob', 'sample' => '1980-05-15'],
            ['label' => 'Years of Experience', 'key' => 'years_experience', 'sample' => '15'],
            ['label' => 'Medical License Number', 'key' => 'medical_license_number', 'sample' => 'MCI-123456'],
            ['label' => 'Blood Group', 'key' => 'blood_group', 'sample' => 'O+'],
            ['label' => 'Gender', 'key' => 'gender', 'sample' => 'male'],
            ['label' => 'Marital Status', 'key' => 'marital_status', 'sample' => 'married'],
            ['label' => 'Languages Known', 'key' => 'languages_known', 'sample' => 'English, Hindi, Punjabi'],
            ['label' => 'Bio', 'key' => 'bio', 'sample' => 'Experienced cardiologist with 15+ years'],
            ['label' => 'Description', 'key' => 'description', 'sample' => 'Detailed professional description'],
            ['label' => 'Status', 'key' => 'status', 'sample' => 'active'],

            // ==================== ADDRESS & CONTACT ====================
            ['label' => 'Address Line 1', 'key' => 'address_line1', 'sample' => '123 Medical Center Road'],
            ['label' => 'Address Line 2', 'key' => 'address_line2', 'sample' => 'Suite 456'],
            ['label' => 'Country', 'key' => 'country', 'sample' => 'India'],
            ['label' => 'State', 'key' => 'state', 'sample' => 'Punjab'],
            ['label' => 'City', 'key' => 'city', 'sample' => 'Ludhiana'],
            ['label' => 'Pincode', 'key' => 'pincode', 'sample' => '141001'],

            // ==================== DEPARTMENT ====================
            ['label' => 'Department', 'key' => 'department', 'sample' => 'Cardiology'],
            ['label' => 'Department Role', 'key' => 'department_role', 'sample' => 'Senior Consultant'],

            // ==================== EDUCATION INFO (JSON Repeater) ====================
            ['label' => 'Education Degree', 'key' => 'education_degree', 'sample' => 'MBBS, MD, DM Cardiology'],
            ['label' => 'Education Institution', 'key' => 'education_institution', 'sample' => 'AIIMS New Delhi'],
            ['label' => 'Education Completion Year', 'key' => 'education_completion_year', 'sample' => '2008'],

            // ==================== AWARDS INFO (JSON Repeater) ====================
            ['label' => 'Award Title', 'key' => 'award_title', 'sample' => 'Best Doctor Award'],
            ['label' => 'Award Year', 'key' => 'award_year', 'sample' => '2020'],
            ['label' => 'Award Description', 'key' => 'award_description', 'sample' => 'For excellence in patient care'],

            // ==================== CERTIFICATIONS INFO (JSON Repeater) ====================
            ['label' => 'Certification Name', 'key' => 'certification_name', 'sample' => 'Board Certified in Cardiology'],
            ['label' => 'Certification Organization', 'key' => 'certification_organization', 'sample' => 'Medical Council of India'],
            ['label' => 'Certification Description', 'key' => 'certification_description', 'sample' => 'Certified by the American Board of Internal Medicine'],

            // ==================== PROFESSIONAL EXPERIENCE (JSON Repeater) ====================
            ['label' => 'Experience Association', 'key' => 'association', 'sample' => 'CMC Ludhiana'],
            ['label' => 'Experience Description', 'key' => 'experience_description', 'sample' => 'Senior Resident'],

            // ==================== AREAS OF EXPERTISE ====================
            ['label' => 'Specializations', 'key' => 'specializations_info', 'sample' => 'Interventional Cardiology, Heart Failure'],
            ['label' => 'Key Procedures', 'key' => 'key_procedures_info', 'sample' => 'Angioplasty, Stent Placement, CABG'],
            ['label' => 'Expertise', 'key' => 'expertise_info', 'sample' => 'Cardiac Catheterization, Echo'],

            // ==================== FELLOWSHIPS INFO (JSON Repeater) ====================
            ['label' => 'Fellowship Title', 'key' => 'fellowship_title', 'sample' => 'FACC'],
            ['label' => 'Fellowship Institution', 'key' => 'fellowship_institution', 'sample' => 'Cleveland Clinic, USA'],
            ['label' => 'Fellowship Year Started', 'key' => 'fellowship_year_started', 'sample' => '2012'],
            ['label' => 'Fellowship Description', 'key' => 'fellowship_description', 'sample' => 'Advanced training in interventional cardiology'],

            // ==================== ADDITIONAL INFORMATION ====================
            ['label' => 'Special Interests', 'key' => 'special_interests', 'sample' => 'Preventive Cardiology, Sports Medicine'],
            ['label' => 'Availability Info', 'key' => 'availability_info', 'sample' => 'Mon-Fri 9AM-5PM, Sat 9AM-1PM'],
            ['label' => 'Memberships', 'key' => 'memberships_info', 'sample' => 'IMA, Cardiological Society of India'],

            // ==================== SOCIAL LINKS (JSON Object) ====================
            ['label' => 'Social Facebook', 'key' => 'social_facebook', 'sample' => 'https://facebook.com/drjohnsmith'],
            ['label' => 'Social Twitter', 'key' => 'social_twitter', 'sample' => 'https://twitter.com/drjohnsmith'],
            ['label' => 'Social LinkedIn', 'key' => 'social_linkedin', 'sample' => 'https://linkedin.com/in/drjohnsmith'],
            ['label' => 'Social Instagram', 'key' => 'social_instagram', 'sample' => 'https://instagram.com/drjohnsmith'],
            ['label' => 'Social Website', 'key' => 'social_website', 'sample' => 'https://drjohnsmith.com'],

            // ==================== MEDIA ====================
            ['label' => 'Avatar', 'key' => 'avatar', 'sample' => 'doctorDocument/avatar.jpg'],

        ];
    }

    public function getTableFilterState(string $name): ?array
    {
        if (property_exists($this, 'tableFilters')) {
            $value = data_get($this->tableFilters, "{$name}.value");
        } elseif (property_exists($this, 'filters')) {
            $value = data_get($this->filters, "{$name}.value");
        } else {
            $value = request()->query("filters.{$name}.value");
        }

        return blank($value) ? null : ['value' => $value];
    }

    #[On('updateTableFilters')]
    public function updatedTableFilters(): void
    {
        $this->dispatch('$refresh');
    }
}