<?php

namespace App\Filament\Imports;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentDoctor;
use App\Models\DoctorAvailability;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * Doctor Excel/CSV Importer
 *
 * CSV Format: Transposed (Field labels in first column, doctor data in subsequent columns)
 * Matches DoctorForm.php field structure exactly
 */
class DoctorExcelImporter
{
    /**
     * Map CSV labels to internal field names
     * Matches DoctorForm.php exactly
     */
    protected array $fieldMap = [
        // ==================== USER TABLE ====================
        'full name' => 'full_name',
        'email' => 'email',
        'phone' => 'phone',

        // ==================== BASIC INFORMATION ====================
        'first name' => 'first_name',
        'last name' => 'last_name',
        'date of birth' => 'dob',
        'years of experience' => 'years_experience',
        'medical license number' => 'medical_license_number',
        'blood group' => 'blood_group',
        'gender' => 'gender',
        'marital status' => 'marital_status',
        'languages known' => 'languages_known',
        'bio' => 'bio',
        'description' => 'description',
        'status' => 'status',

        // ==================== ADDRESS & CONTACT ====================
        'address line 1' => 'address_line1',
        'address line 2' => 'address_line2',
        'country' => 'country',
        'state' => 'state',
        'city' => 'city',
        'pincode' => 'pincode',

        // ==================== DEPARTMENT ====================
        'department' => 'department',
        'department role' => 'department_role',

        // ==================== EDUCATION INFO (JSON Repeater) ====================
        // Fields: degree, institution, completion_year
        'education degree' => 'education_degree',
        'education institution' => 'education_institution',
        'education completion year' => 'education_completion_year',

        // ==================== AWARDS INFO (JSON Repeater) ====================
        // Fields: title, year, description
        'award title' => 'award_title',
        'award year' => 'award_year',
        'award description' => 'award_description',

        // ==================== CERTIFICATIONS INFO (JSON Repeater) ====================
        // Fields: name, organization, description
        'certification name' => 'certification_name',
        'certification organization' => 'certification_organization',
        'certification description' => 'certification_description',

        // ==================== PROFESSIONAL EXPERIENCE (JSON Repeater) ====================
        // Fields: association, description
        'experience association' => 'association',
        'experience description' => 'experience_description',

        // ==================== AREAS OF EXPERTISE ====================
        'specializations' => 'specializations_info',
        'key procedures' => 'key_procedures_info',
        'expertise' => 'expertise_info',

        // ==================== FELLOWSHIPS INFO (JSON Repeater) ====================
        // Fields: title, institution, year_started, description
        'fellowship title' => 'fellowship_title',
        'fellowship institution' => 'fellowship_institution',
        'fellowship year started' => 'fellowship_year_started',
        'fellowship description' => 'fellowship_description',

        // ==================== ADDITIONAL INFORMATION ====================
        'special interests' => 'special_interests',
        'availability info' => 'availability_info',
        'memberships' => 'memberships_info',

        // ==================== SOCIAL LINKS (JSON Object) ====================
        // Fields: facebook, twitter, linkedin, instagram, website
        'social facebook' => 'social_facebook',
        'social twitter' => 'social_twitter',
        'social linkedin' => 'social_linkedin',
        'social instagram' => 'social_instagram',
        'social website' => 'social_website',

        // ==================== MEDIA ====================
        'avatar' => 'avatar',

        // // ==================== AVAILABILITY SLOT ====================
        // // Fields: day_of_week, start_time, end_time, is_recurring, consultation_type, opd_type, consultation_fee, capacity, is_available
        // 'slot day' => 'slot_day',
        // 'slot start time' => 'slot_start_time',
        // 'slot end time' => 'slot_end_time',
        // 'slot is recurring' => 'slot_is_recurring',
        // 'slot consultation type' => 'slot_consultation_type',
        // 'slot opd type' => 'slot_opd_type',
        // 'slot consultation fee' => 'slot_consultation_fee',
        // 'slot capacity' => 'slot_capacity',
        // 'slot doctor room' => 'slot_doctor_room',
        // 'slot date' => 'slot_date',
        // 'slot is available' => 'slot_is_available',
    ];

    /**
     * Import doctors from CSV file
     */
    public function import(string $filePath): array
    {
        $results = [
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $results['errors'][] = 'Could not open file';
            return $results;
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            $results['errors'][] = 'File is empty';
            return $results;
        }

        $doctors = $this->parseTransposedData($rows);

        if (empty($doctors)) {
            $results['errors'][] = 'No valid doctor data found. Ensure CSV follows the template format.';
            return $results;
        }

        foreach ($doctors as $index => $doctorData) {
            try {
                $result = $this->importDoctor($doctorData);

                if ($result === 'exists') {
                    $results['skipped']++;
                    $name = $doctorData['full_name'] ?? $doctorData['first_name'] ?? "Doctor #" . ($index + 1);
                    $results['errors'][] = "{$name}: Already exists in database (duplicate email)";
                } elseif ($result === 'exists_updated') {
                    $results['successful']++;
                } else {
                    $results['successful']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $name = $doctorData['full_name'] ?? $doctorData['first_name'] ?? "Doctor #" . ($index + 1);
                $results['errors'][] = "{$name}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Parse transposed CSV data
     */
    protected function parseTransposedData(array $rows): array
    {
        $doctors = [];
        $fieldData = [];

        foreach ($rows as $row) {
            if (empty($row) || empty(trim($row[0] ?? ''))) {
                continue;
            }

            $rawLabel = trim(rtrim($row[0], ':'));
            $label = strtolower($rawLabel);
            $fieldName = $this->fieldMap[$label] ?? null;

            if ($fieldName) {
                for ($col = 1; $col < count($row); $col++) {
                    if (!isset($fieldData[$col])) {
                        $fieldData[$col] = [];
                    }
                    $fieldData[$col][$fieldName] = trim($row[$col] ?? '');
                }
            }
        }

        foreach ($fieldData as $data) {
            $name = $this->clean($data['full_name'] ?? null) ?? $this->clean($data['first_name'] ?? null);
            if ($name) {
                $doctors[] = $data;
            }
        }

        return $doctors;
    }

    /**
     * Import a single doctor
     */
    protected function importDoctor(array $data): string
    {
        return DB::transaction(function () use ($data) {
            // Parse name
            $firstName = $this->clean($data['first_name'] ?? null);
            $lastName = $this->clean($data['last_name'] ?? null);

            if (!$firstName && ($fullName = $this->clean($data['full_name'] ?? null))) {
                $parsed = $this->parseName($fullName);
                $firstName = $parsed['first'];
                $lastName = $parsed['last'];
            }

            if (empty($firstName)) {
                throw new \Exception('First name is required');
            }

            // Email
            $email = $this->clean($data['email'] ?? null);
            if (empty($email)) {
                $email = Str::slug($firstName . ' ' . $lastName) . '-' . Str::random(4) . '@hospital.local';
            }

            // Check if exists
            $user = User::where('email', $email)->first();
            $isExisting = true;

            if (!$user) {
                $isExisting = false;
                // Create User
                $user = User::create([
                    'name' => trim($firstName . ' ' . $lastName),
                    'email' => $email,
                    'password' => Hash::make(Str::random(12)),
                    'phone' => $this->clean($data['phone'] ?? null),
                ]);

                if (method_exists($user, 'assignRole')) {
                    try {
                        $user->assignRole('doctor');
                    } catch (\Exception $e) {
                    }
                }
            } else {
                // Update basic user info if needed
                $user->name = trim($firstName . ' ' . $lastName);
                $phone = $this->clean($data['phone'] ?? null);
                if ($phone) {
                    $user->phone = $phone;
                }
            }

            // Create or get Doctor
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor) {
                $doctor = new Doctor();
                $doctor->user_id = $user->id;
                $isExisting = false; // if doctor missing but user exists, still treat as new doctor insert overall
            }

            // ==================== BASIC INFORMATION ====================
            $doctor->first_name = $firstName;
            $doctor->last_name = $lastName ?? '';
            $doctor->dob = $this->clean($data['dob'] ?? null);
            $doctor->years_experience = (int) ($this->clean($data['years_experience'] ?? null) ?? 0);
            $doctor->medical_license_number = $this->clean($data['medical_license_number'] ?? null);
            $doctor->blood_group = $this->clean($data['blood_group'] ?? null);
            $doctor->gender = $this->parseEnum($data['gender'] ?? null, ['male', 'female', 'other']);
            $doctor->marital_status = $this->parseEnum($data['marital_status'] ?? null, ['single', 'married', 'divorced', 'widowed']);
            $doctor->bio = $this->clean($data['bio'] ?? null);
            $doctor->description = $this->clean($data['description'] ?? null);

            if ($status = $this->parseEnum($data['status'] ?? null, \App\Enums\DoctorStatus::values())) {
                $doctor->status = $status;
            }

            // Languages Known (stored as JSON array in DB, comma-separated in CSV)
            $languages = $this->clean($data['languages_known'] ?? null);
            if ($languages) {
                $doctor->languages_known = array_map('trim', explode(',', $languages));
            }

            // ==================== ADDRESS & CONTACT ====================
            $doctor->address_line1 = $this->clean($data['address_line1'] ?? null);
            $doctor->address_line2 = $this->clean($data['address_line2'] ?? null);
            $doctor->country = $this->clean($data['country'] ?? null);
            $doctor->state = $this->clean($data['state'] ?? null);
            $doctor->city = $this->clean($data['city'] ?? null);
            $doctor->pincode = $this->clean($data['pincode'] ?? null);

            // ==================== AREAS OF EXPERTISE ====================
            $doctor->specializations_info = $this->clean($data['specializations_info'] ?? null);
            $doctor->key_procedures_info = $this->clean($data['key_procedures_info'] ?? null);
            $doctor->expertise_info = $this->clean($data['expertise_info'] ?? null);

            // ==================== ADDITIONAL INFORMATION ====================
            $doctor->special_interests = $this->clean($data['special_interests'] ?? null);
            $doctor->availability_info = $this->clean($data['availability_info'] ?? null);
            $doctor->memberships_info = $this->clean($data['memberships_info'] ?? null);

            // ==================== EDUCATION INFO (JSON Repeater) ====================
            // Fields: degree, institution, completion_year
            $educationDegree = $this->clean($data['education_degree'] ?? null);
            if ($educationDegree) {
                $doctor->education_info = [[
                    'degree' => $educationDegree,
                    'institution' => $this->clean($data['education_institution'] ?? null),
                    'completion_year' => $this->clean($data['education_completion_year'] ?? null),
                ]];
            }

            // ==================== AWARDS INFO (JSON Repeater) ====================
            // Fields: title, year, description
            $awardTitle = $this->clean($data['award_title'] ?? null);
            if ($awardTitle) {
                $doctor->awards_info = [[
                    'title' => $awardTitle,
                    'year' => $this->clean($data['award_year'] ?? null),
                    'description' => $this->clean($data['award_description'] ?? null),
                ]];
            }

            // ==================== CERTIFICATIONS INFO (JSON Repeater) ====================
            // Fields: name, organization, description
            $certName = $this->clean($data['certification_name'] ?? null);
            if ($certName) {
                $doctor->certifications_info = [[
                    'name' => $certName,
                    'organization' => $this->clean($data['certification_organization'] ?? null),
                    'description' => $this->clean($data['certification_description'] ?? null),
                ]];
            }

            // ==================== PROFESSIONAL EXPERIENCE (JSON Repeater) ====================
            // Fields: association, description
            $association = $this->clean($data['association'] ?? null);
            $expDesc = $this->clean($data['experience_description'] ?? null);
            if ($association || $expDesc) {
                $doctor->professional_experience_info = [[
                    'association' => $association,
                    'description' => $expDesc,
                ]];
            }

            // ==================== FELLOWSHIPS INFO (JSON Repeater) ====================
            // Fields: title, institution, year_started, description
            $fellowshipTitle = $this->clean($data['fellowship_title'] ?? null);
            if ($fellowshipTitle) {
                $doctor->fellowships_info = [[
                    'title' => $fellowshipTitle,
                    'institution' => $this->clean($data['fellowship_institution'] ?? null),
                    'year_started' => $this->clean($data['fellowship_year_started'] ?? null),
                    'description' => $this->clean($data['fellowship_description'] ?? null),
                ]];
            }

            // ==================== SOCIAL LINKS (JSON Object) ====================
            // Fields: facebook, twitter, linkedin, instagram, website
            $socialLinks = [];
            if ($fb = $this->clean($data['social_facebook'] ?? null)) $socialLinks['facebook'] = $fb;
            if ($tw = $this->clean($data['social_twitter'] ?? null)) $socialLinks['twitter'] = $tw;
            if ($li = $this->clean($data['social_linkedin'] ?? null)) $socialLinks['linkedin'] = $li;
            if ($ig = $this->clean($data['social_instagram'] ?? null)) $socialLinks['instagram'] = $ig;
            if ($ws = $this->clean($data['social_website'] ?? null)) $socialLinks['website'] = $ws;
            if (!empty($socialLinks)) {
                $doctor->social_links = $socialLinks;
            }

            // ==================== MEDIA ====================
            if ($avatar = $this->clean($data['avatar'] ?? null)) {
                // If the avatar is a JSON string of an array, extract the first item
                if (str_starts_with(trim($avatar), '[') && str_ends_with(trim($avatar), ']')) {
                    $decoded = json_decode($avatar, true);
                    if (is_array($decoded) && !empty($decoded)) {
                        $avatar = array_values($decoded)[0];
                    }
                }

                $avatar = stripslashes($avatar);

                // If avatar is a full URL, download the file to local storage
                if (str_starts_with($avatar, 'http')) {
                    $parsedUrl = parse_url($avatar, PHP_URL_PATH);
                    $relativePath = null;

                    if ($parsedUrl && str_contains($parsedUrl, '/storage/')) {
                        $relativePath = explode('/storage/', $parsedUrl, 2)[1];
                    }

                    // Try to download the file if it doesn't already exist locally
                    if ($relativePath && !\Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)) {
                        try {
                            $fileContents = @file_get_contents($avatar);
                            if ($fileContents !== false) {
                                \Illuminate\Support\Facades\Storage::disk('public')->put($relativePath, $fileContents);
                            }
                        } catch (\Exception $e) {
                            // Silently fail — file may not be accessible
                        }
                    }

                    $avatar = $relativePath ?? $avatar;
                }

                // Set avatar on both models — the InteractsWithModuleDocuments trait
                // handles saving to module_documents table via savePendingModuleDocuments()
                $user->avatar = $avatar;
                $doctor->avatar = $avatar;
            }

            // Save user and doctor — the saved event triggers savePendingModuleDocuments()
            // which persists avatar (and signature) to the module_documents table
            $user->save();
            $doctor->save();

            // ==================== DEPARTMENT ====================
            $departmentName = $this->clean($data['department'] ?? null);
            $departmentRole = $this->clean($data['department_role'] ?? null);
            if ($departmentName) {
                $department = Department::firstOrCreate(
                    ['name' => $departmentName],
                    ['slug' => Str::slug($departmentName)]
                );

                DepartmentDoctor::create([
                    'doctor_id' => $doctor->id,
                    'department_id' => $department->id,
                    'role' => $departmentRole,
                    'order' => 1,
                ]);
            }



            return $isExisting ? 'exists_updated' : 'success';
        });
    }

    /**
     * Parse full name into first/last
     */
    protected function parseName(string $name): array
    {
        $name = preg_replace('/^Dr\.?\s*/i', '', trim($name));
        $parts = preg_split('/\s+/', $name, 2);

        return [
            'first' => $parts[0] ?? '',
            'last' => $parts[1] ?? '',
        ];
    }

    /**
     * Clean value - return null for empty values
     */
    protected function clean(?string $value): ?string
    {
        if ($value === null) return null;

        $value = trim($value);
        $lower = strtolower($value);

        if ($value === '' || $value === '-' || $lower === 'na' || $lower === 'n/a' || $lower === 'none' || $lower === 'null') {
            return null;
        }

        return $value;
    }

    /**
     * Parse enum value
     */
    protected function parseEnum(?string $value, array $validOptions): ?string
    {
        $clean = $this->clean($value);
        if ($clean === null) return null;

        $lower = strtolower($clean);
        return in_array($lower, $validOptions) ? $lower : null;
    }
}
