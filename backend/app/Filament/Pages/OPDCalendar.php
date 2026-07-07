<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\{InteractsWithForms};
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use App\Models\{DoctorAvailability, Doctor, Department};
use App\Enums\DayOfWeek;
use Illuminate\Support\Carbon;
use UnitEnum;
use Filament\Forms\Components\TimePicker;
use App\Enums\AppointmentStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\{FileUpload, DatePicker, Select, Toggle, TextInput, Repeater};
use Filament\Notifications\Notification;
use App\Traits\HasCustomSidebar;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\User;
use App\Services\DoctorAvailabilityValidationService;
use App\Services\DoctorAvailabilityService;
use App\Services\SlotCapacityService;

class OPDCalendar extends Page implements HasForms
{
    use InteractsWithForms;
    use HasCustomSidebar;

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'OPD Calendar',
            'icon'  => 'heroicon-o-calendar-days',
            'sort'  => 3,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $module = static::$slug ?? strtolower(class_basename(static::class));
        return check_permission(["{$module}.view", "{$module}.view_any"]);
    }
    protected string $view = 'filament.pages.o-p-d-calendar';
    protected static ?string $slug = 'opd-schedule';
    protected static ?string $title = 'OPD Schedule';

    protected function getActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(fn() => $this->downloadTemplate()),

                Action::make('bulkImportSlots')
                    ->label('Bulk Import Slots')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->modalWidth('5xl')
                    ->slideOver()
                    ->modalHeading('Bulk Import Doctor Slots')
                    ->form([
                        FileUpload::make('file')
                            ->label('Upload Excel/CSV')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv'
                            ])
                            ->disk('local')
                            ->directory('imports')
                            ->preserveFilenames()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if (!$state) return;
                                // Allow unlimited memory to handle large file parsing and Filament Repeater previews
                                ini_set('memory_limit', '-1');

                                try {
                                    \Illuminate\Support\Facades\Log::info('Importing file. State: ' . json_encode($state));

                                    $data = [];
                                    $path = $state;

                                    if (is_array($state)) {
                                        $path = (array_key_first($state) ?: current($state));
                                    }

                                    // If it's a TemporaryUploadedFile object, we can use its methods
                                    if ($path instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                        $absolutePath = $path->getRealPath();
                                        if (file_exists($absolutePath)) {
                                            if (($handle = fopen($absolutePath, 'r')) !== false) {
                                                while (($row = fgetcsv($handle)) !== false) {
                                                    $data[] = $row;
                                                }
                                                fclose($handle);
                                            }
                                        }
                                    }

                                    if (empty($data)) {
                                        $pathStr = is_string($path) ? $path : (is_object($path) && method_exists($path, '__toString') ? (string)$path : null);
                                        if (!$pathStr) throw new \Exception("Could not determine file path.");

                                        // Try reading via Storage facade (disk-relative)
                                        $exists = Storage::disk('local')->exists($pathStr);
                                        if ($exists) {
                                            $stream = Storage::disk('local')->readStream($pathStr);
                                            if ($stream) {
                                                while (($row = fgetcsv($stream)) !== false) {
                                                    $data[] = $row;
                                                }
                                                fclose($stream);
                                            }
                                        }

                                        // Try reading via absolute path fallback
                                        if (empty($data)) {
                                            $absolutePath = str_starts_with($pathStr, '/') ? $pathStr : Storage::disk('local')->path($pathStr);
                                            if (file_exists($absolutePath) && is_file($absolutePath)) {
                                                if (($handle = fopen($absolutePath, 'r')) !== false) {
                                                    while (($row = fgetcsv($handle)) !== false) {
                                                        $data[] = $row;
                                                    }
                                                    fclose($handle);
                                                }
                                            }
                                        }

                                        // Try reading via Maatwebsite\Excel as fallback (handles BOM and encodings better)
                                        if (empty($data)) {
                                            try {
                                                $absolutePath = str_starts_with($pathStr, '/') ? $pathStr : Storage::disk('local')->path($pathStr);
                                                if (file_exists($absolutePath)) {
                                                    $excelData = \Maatwebsite\Excel\Facades\Excel::toArray(new \stdClass(), $absolutePath, null, \Maatwebsite\Excel\Excel::CSV);
                                                    $data = $excelData[0] ?? [];
                                                }
                                            } catch (\Exception $e) {
                                            }
                                        }
                                    }

                                    if (empty($data)) {
                                        throw new \Exception("The file could not be read. Please ensure it is a valid CSV file.");
                                    }

                                    $extension = strtolower(pathinfo(is_string($path) ? $path : (string)$path, PATHINFO_EXTENSION));


                                    // Detect if this is the wrong file format (transposed Doctor Export)
                                    $firstCell = trim((string)($data[0][0] ?? ''));
                                    if (str_ends_with($firstCell, ':') || strtolower($firstCell) === 'full name') {
                                        throw new \Exception("This file appears to be a Doctor Profile export. Please use the Doctor Slots template instead.");
                                    }

                                    if (count($data) <= 1) return;

                                    $rows = array_slice($data, 1);
                                    $vService = app(DoctorAvailabilityValidationService::class);

                                    $preview = [];
                                    $emailCache = [];
                                    $totalRows = count($rows);
                                    $rowsWithEmptyEmail = 0;
                                    $rowsWithUnknownDoctor = 0;
                                    foreach ($rows as $index => $row) {
                                        $email = trim($row[0] ?? '');
                                        if (empty($email)) {
                                            $rowsWithEmptyEmail++;
                                            continue; // Skip empty emails completely
                                        }

                                        if (!array_key_exists($email, $emailCache)) {
                                            $user = User::with('doctor')->where('email', $email)->first();
                                            $emailCache[$email] = [
                                                'name' => $user ? $user->name : null,
                                                'doctor_id' => $user?->doctor?->id
                                            ];
                                        }

                                        $doctorId = $emailCache[$email]['doctor_id'];
                                        if (!$doctorId) {
                                            $rowsWithUnknownDoctor++;
                                            continue;
                                        }

                                        $doctorName = $emailCache[$email]['name'];
                                        $isRecurring = in_array(strtolower(trim((string)($row[9] ?? ''))), ['true', '1', 'yes', 'y']);

                                        $rStart = $vService->normalizeDate($row[10] ?? null);
                                        $dayOrDate = trim($row[1] ?? '');

                                        // Calculate Day if recurring and missing
                                        if ($isRecurring && empty($dayOrDate) && $rStart) {
                                            $dayOrDate = \Carbon\Carbon::parse($rStart)->format('l');
                                        } elseif (!$isRecurring && $dayOrDate) {
                                            try {
                                                $dayOrDate = \Carbon\Carbon::parse($dayOrDate)->format('Y-m-d');
                                            } catch (\Exception $e) {
                                            }
                                        }

                                        $preview[] = [
                                            'doctor_name' => $doctorName,
                                            'doctor_id' => $doctorId,
                                            'doctor_email' => $email,
                                            'day_or_date' => ucfirst($dayOrDate),
                                            'start_time' => $vService->normalizeTime($row[2] ?? null),
                                            'end_time' => $vService->normalizeTime($row[3] ?? null),
                                            'capacity' => (int)($row[4] ?? 1),
                                            'consultation_type' => strtolower($row[5] ?? 'in-person'),
                                            'opd_type' => $row[6] ?? (strtolower($row[5] ?? '') === 'video' ? null : 'general'),
                                            'doctor_room' => $row[7] ?? '',
                                            'fee' => $row[8] ?? 0,
                                            'is_recurring' => $isRecurring,
                                            'recurring_start_date' => $rStart,
                                            'recurring_end_date' => $vService->normalizeDate($row[11] ?? null),
                                            'recurring_months' => $row[12] ?? 3,
                                        ];
                                    }

                                    $grouped = [
                                        'slots_monday' => [],
                                        'slots_tuesday' => [],
                                        'slots_wednesday' => [],
                                        'slots_thursday' => [],
                                        'slots_friday' => [],
                                        'slots_saturday' => [],
                                        'slots_sunday' => [],
                                        'slots_one_time' => []
                                    ];

                                    foreach ($preview as $slot) {
                                        $d = strtolower($slot['day_or_date']);
                                        $k = 'slots_' . $d;
                                        if (!$slot['is_recurring'] || !array_key_exists($k, $grouped)) {
                                            $grouped['slots_one_time'][] = $slot;
                                        } else {
                                            $grouped[$k][] = $slot;
                                        }
                                    }

                                    foreach ($grouped as $k => $arr) {
                                        $set($k, $arr);
                                    }

                                    $previewCount = count($preview);
                                    $skippedAtPreview = $totalRows - $previewCount;
                                    if ($skippedAtPreview > 0) {
                                        \Illuminate\Support\Facades\Log::warning('OPD bulk import preview skipped rows', [
                                            'total_rows' => $totalRows,
                                            'preview_rows' => $previewCount,
                                            'skipped_rows' => $skippedAtPreview,
                                            'rows_with_empty_email' => $rowsWithEmptyEmail,
                                            'rows_with_unknown_doctor' => $rowsWithUnknownDoctor,
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()->title('File parsing failed')->body($e->getMessage())->danger()->send();
                                }
                            }),

                        \Filament\Forms\Components\Placeholder::make('upload_prompt')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('<div class="p-8 text-center bg-gray-50 border border-dashed border-gray-300 rounded-xl dark:bg-gray-800/50 dark:border-gray-700"><svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg><h3 class="text-lg font-medium text-gray-900 dark:text-white">Please upload a file</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload your CSV or Excel file above to automatically extract and preview consultation slots.</p></div>'))
                            ->visible(fn($get) => empty($get('slots_monday')) && empty($get('slots_tuesday')) && empty($get('slots_wednesday')) && empty($get('slots_thursday')) && empty($get('slots_friday')) && empty($get('slots_saturday')) && empty($get('slots_sunday')) && empty($get('slots_one_time'))),

                        ...array_map(function ($dayKey, $dayName) {
                            return \Filament\Schemas\Components\Section::make()
                                ->heading(fn($get) => $dayName . ' (' . count($get($dayKey) ?? []) . ')')
                                ->description($dayKey === 'slots_one_time' ? 'One-time consultation slots' : "Consultation slots for $dayName")
                                ->icon('heroicon-o-clock')
                                ->visible(fn($get) => !empty($get($dayKey)))
                                ->schema([
                                    Repeater::make($dayKey)
                                        ->hiddenLabel()
                                        ->defaultItems(0)
                                        ->itemLabel(function (array $state) use ($dayName): string {
                                            $name = $state['doctor_name'] ?? 'Doctor';
                                            if (trim($name) === '') $name = 'Doctor';

                                            $dayOrDate = $state['day_or_date'] ?? '';
                                            $start = $state['start_time'] ?? '';
                                            $end = $state['end_time'] ?? '';
                                            $capacity = $state['capacity'] ?? 1;

                                            if ($start) $start = \Carbon\Carbon::parse($start)->format('h:i A');
                                            if ($end) $end = \Carbon\Carbon::parse($end)->format('h:i A');
                                            $time = ($start && $end) ? "$start - $end" : '';

                                            $isRecurring = $state['is_recurring'] ?? false;

                                            if ($isRecurring) {
                                                $rStart = $state['recurring_start_date'] ?? '';
                                                $rEnd = $state['recurring_end_date'] ?? '';
                                                if ($rStart) $rStart = \Carbon\Carbon::parse($rStart)->format('M d, Y');
                                                if ($rEnd) $rEnd = \Carbon\Carbon::parse($rEnd)->format('M d, Y');
                                                return "$name | $dayName ($rStart - $rEnd) | $time | Cap: $capacity";
                                            } else {
                                                $dateObj = null;
                                                try {
                                                    if ($dayOrDate) $dateObj = \Carbon\Carbon::parse($dayOrDate);
                                                } catch (\Exception $e) {
                                                }
                                                $dateStr = $dateObj ? $dateObj->format('M d, Y') : 'No Date';
                                                return "$name | $dateStr | $time | Cap: $capacity";
                                            }
                                        })
                                        ->schema([
                                            // Hidden fields natively store the data, preventing data loss on form submission
                                            \Filament\Forms\Components\Hidden::make('doctor_id'),
                                            \Filament\Forms\Components\Hidden::make('doctor_email'),
                                            \Filament\Forms\Components\Hidden::make('capacity'),
                                            \Filament\Forms\Components\Hidden::make('doctor_room'),
                                            \Filament\Forms\Components\Hidden::make('fee'),
                                            \Filament\Forms\Components\Hidden::make('recurring_months'),
                                            \Filament\Forms\Components\Hidden::make('doctor_name'),
                                            \Filament\Forms\Components\Hidden::make('day_or_date'),
                                            \Filament\Forms\Components\Hidden::make('start_time'),
                                            \Filament\Forms\Components\Hidden::make('end_time'),
                                            \Filament\Forms\Components\Hidden::make('consultation_type'),
                                            \Filament\Forms\Components\Hidden::make('opd_type'),
                                            \Filament\Forms\Components\Hidden::make('is_recurring'),
                                            \Filament\Forms\Components\Hidden::make('recurring_start_date'),
                                            \Filament\Forms\Components\Hidden::make('recurring_end_date'),

                                            \Filament\Forms\Components\Placeholder::make('summary_view')
                                                ->hiddenLabel()
                                                ->content(function ($get) {
                                                    $name = $get('doctor_name');
                                                    $email = $get('doctor_email');
                                                    $dayOrDate = $get('day_or_date');
                                                    $type = ucfirst($get('consultation_type'));
                                                    $capacity = $get('capacity') ?? '1';
                                                    $isRec = $get('is_recurring');

                                                    try {
                                                        $start = $get('start_time') ? \Carbon\Carbon::parse($get('start_time'))->format('h:i A') : '';
                                                        $end = $get('end_time') ? \Carbon\Carbon::parse($get('end_time'))->format('h:i A') : '';
                                                    } catch (\Exception $e) {
                                                        $start = $get('start_time');
                                                        $end = $get('end_time');
                                                    }

                                                    if ($isRec) {
                                                        $rStart = $get('recurring_start_date') ? \Carbon\Carbon::parse($get('recurring_start_date'))->format('M d, Y') : '';
                                                        $rEnd = $get('recurring_end_date') ? \Carbon\Carbon::parse($get('recurring_end_date'))->format('M d, Y') : '';

                                                        return new \Illuminate\Support\HtmlString("
                                                            <div class='p-5 bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 rounded-xl space-y-3'>
                                                                <div class='flex justify-start items-center gap-4'>
                                                                    <h4 class='text-[16px] font-semibold text-gray-900 dark:text-white flex items-center gap-2'>
                                                                        <svg class='w-5 h-5 text-primary-500' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z' /></svg>
                                                                        $name ($email)
                                                                    </h4>
                                                                       <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                                                            " . ($isRec ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300') . "'>
                                                                            " . ($isRec ? 'Recurring' : 'One-time') . "
                                                                        </span>
                                                                </div>
                                                                <div class='flex flex-wrap items-center gap-5 gap-y-2 text-sm text-gray-500 dark:text-gray-400 font-medium'>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' /></svg> $start - $end</div>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5' /></svg> $rStart to $rEnd</div>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z' /></svg> Cap: $capacity</div>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z' /></svg> $type</div>
                                                                </div>

                                                            </div>
                                                        ");
                                                    } else {
                                                        try {
                                                            $dateStr = \Carbon\Carbon::parse($dayOrDate)->format('M d, Y');
                                                        } catch (\Exception $e) {
                                                            $dateStr = $dayOrDate;
                                                        }
                                                        return new \Illuminate\Support\HtmlString("
                                                            <div class='p-5 bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 rounded-xl space-y-3'>
                                                                <div class='flex justify-start gap-4 items-center'>
                                                                    <h4 class='text-[16px] font-semibold text-gray-900 dark:text-white flex items-center gap-2'>
                                                                        <svg class='w-5 h-5 text-primary-500' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z' /></svg>
                                                                        $name ($email)
                                                                    </h4>
                                                                    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                                                            " . (!$isRec ? 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : '') . "'>
                                                                            " . (!$isRec ? 'One Time' : 'Unknown') . "
                                                                    </span>
                                                                </div>
                                                                <div class='flex flex-wrap items-center gap-5 gap-y-2 text-sm text-gray-500 dark:text-gray-400 font-medium'>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' /></svg> $start - $end</div>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5' /></svg> $dateStr</div>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z' /></svg> Cap: $capacity</div>
                                                                    <div class='flex items-center gap-1.5'><svg class='w-4 h-4' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z' /></svg> $type</div>
                                                                </div>
                                                            </div>
                                                        ");
                                                    }
                                                }),
                                        ])
                                        ->addable(false)
                                        ->reorderable(false)
                                        ->collapsed()
                                ]);
                        }, array_keys(['slots_monday' => 'Monday', 'slots_tuesday' => 'Tuesday', 'slots_wednesday' => 'Wednesday', 'slots_thursday' => 'Thursday', 'slots_friday' => 'Friday', 'slots_saturday' => 'Saturday', 'slots_sunday' => 'Sunday', 'slots_one_time' => 'One Time']), ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'One Time'])
                    ])
                    ->action(function (array $data) {
                        try {
                            $slots = array_merge(
                                $data['slots_monday'] ?? [],
                                $data['slots_tuesday'] ?? [],
                                $data['slots_wednesday'] ?? [],
                                $data['slots_thursday'] ?? [],
                                $data['slots_friday'] ?? [],
                                $data['slots_saturday'] ?? [],
                                $data['slots_sunday'] ?? [],
                                $data['slots_one_time'] ?? []
                            );
                            if (empty($slots)) {
                                Notification::make()->title('No slots to import')->warning()->send();
                                return;
                            }

                            $service = app(DoctorAvailabilityService::class);
                            $groupedByDoctor = [];
                            $successful = 0;
                            $skipped = 0;
                            $errors = [];

                            foreach ($slots as $slot) {
                                if (empty($slot['doctor_id'])) {
                                    $errors[] = "Skipped row for {$slot['doctor_email']}: Doctor not found.";
                                    $skipped++;
                                    continue;
                                }

                                // Resolve Day
                                $day = null;
                                if ($slot['is_recurring'] && $slot['recurring_start_date']) {
                                    $day = strtolower(Carbon::parse($slot['recurring_start_date'])->format('l'));
                                } elseif (!empty($slot['day_or_date'])) {
                                    $d = strtolower(trim((string)$slot['day_or_date']));
                                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                    if (in_array($d, $days)) {
                                        $day = $d;
                                    } else {
                                        try {
                                            $day = strtolower(Carbon::parse($slot['day_or_date'])->format('l'));
                                        } catch (\Exception $e) {
                                        }
                                    }
                                }

                                if (!$day) {
                                    $errors[] = "Skipped row for {$slot['doctor_name']}: Could not resolve day.";
                                    $skipped++;
                                    continue;
                                }

                                $groupedByDoctor[$slot['doctor_id']]["slots_{$day}"][] = [
                                    'date' => $slot['is_recurring'] ? null : $slot['day_or_date'],
                                    'start_time' => $slot['start_time'],
                                    'end_time' => $slot['end_time'],
                                    'capacity' => $slot['capacity'],
                                    'consultation_type' => $slot['consultation_type'],
                                    'opd_type' => $slot['opd_type'],
                                    'doctor_room' => $slot['doctor_room'],
                                    'consultation_fee' => $slot['fee'],
                                    'is_recurring' => $slot['is_recurring'],
                                    'recurring_start_date' => $slot['recurring_start_date'],
                                    'recurring_end_date' => $slot['recurring_end_date'],
                                    'recurring_months' => $slot['recurring_months'],
                                ];
                            }

                            foreach ($groupedByDoctor as $doctorId => $dataGroup) {
                                $doctor = Doctor::find($doctorId);
                                if ($doctor) {
                                    $res = $service->persistAvailabilitySlots($doctor, $dataGroup, false, false);
                                    $successful += ($res['totalSaved'] + $res['totalUpdated']);
                                    $skipped += $res['totalSkipped'];
                                    if (!empty($res['errors'])) {
                                        $errors = array_merge($errors, $res['errors']);
                                    }
                                }
                            }

                            \Illuminate\Support\Facades\Log::info('OPD bulk import finished', [
                                'total_preview_slots' => count($slots),
                                'successful' => $successful,
                                'skipped' => $skipped,
                                'error_count' => count($errors),
                                'sample_errors' => array_slice($errors, 0, 20),
                            ]);

                            $notification = Notification::make()
                                ->title('Import Completed')
                                ->body("{$successful} slots created/updated. {$skipped} skipped.")
                                ->success();

                            if (!empty($errors)) {
                                $notification->warning();
                                $notification->body($notification->getBody() . " Some issues occurred. Check logs.");
                            }

                            $notification->send();
                            $this->loadScheduleByViewMode();
                        } catch (\Exception $e) {
                            Notification::make()->title('Import failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('exportSlots')
                    ->label('Export All Slots')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn() => $this->exportSlots()),
            ])
                ->label('Import / Export')
                ->icon('heroicon-o-arrow-path')
                ->button()
                ->color('white'),

            Action::make('addEvent')
                ->label('Add Event')
                ->slideOver()
                ->color('white')
                ->extraAttributes([
                    'class' => '[&_.fi-btn-spinner]:!text-white [&_svg.animate-spin]:!text-white',
                ])
                ->form([
                    Select::make('doctor_id')
                        ->label('Doctor')
                        ->options(
                            Doctor::active()->get()->mapWithKeys(
                                fn($d) => [$d->id => trim(($d->title ? $d->title . ' ' : '') . $d->first_name . ' ' . $d->last_name)]
                            )
                        )
                        ->searchable()
                        ->required(),

                    Select::make('day_of_week')
                        ->label('Day')
                        ->options(DayOfWeek::labels())
                        ->default(strtolower(now()->format('l')))
                        ->live()
                        ->required()
                        ->afterStateUpdated(fn($set) => $set('date', null)),

                    Toggle::make('is_recurring')
                        ->label('Recurring?')
                        ->onColor('success')
                        ->reactive(),

                    DatePicker::make('date')
                        ->label('Date')
                        ->required(fn($get) => !$get('is_recurring'))
                        ->visible(fn($get) => !$get('is_recurring'))
                        ->live()
                        ->prefixAction(
                            Action::make('prev_date')
                                ->icon('heroicon-m-chevron-left')
                                ->action(function ($state, $set) {
                                    if (!$state) {
                                        return;
                                    }
                                    $current = \Carbon\Carbon::parse($state);
                                    $newDate = $current->subWeek();
                                    if ($newDate->lt(now()->startOfDay())) {
                                        return;
                                    }
                                    $set('date', $newDate->format('Y-m-d'));
                                })
                                ->disabled(fn($state) => !$state || \Carbon\Carbon::parse($state)->subWeek()->lt(now()->startOfDay()))
                        )
                        ->suffixAction(
                            Action::make('next_date')
                                ->icon('heroicon-m-chevron-right')
                                ->action(function ($state, $set, $get) {
                                    if (!$state) {
                                        $day = $get('day_of_week');
                                        if (!$day) {
                                            return;
                                        }
                                        $set('date', \Carbon\Carbon::parse("next $day")->format('Y-m-d'));

                                        return;
                                    }
                                    $current = \Carbon\Carbon::parse($state);
                                    $set('date', $current->addWeek()->format('Y-m-d'));
                                })
                        )
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (!$state) {
                                return;
                            }
                            $date = \Carbon\Carbon::parse($state);
                            $day = strtolower($get('day_of_week') ?? '');
                            if ($day && strtolower($date->format('l')) !== $day) {
                                $set('date', null);
                                \Filament\Notifications\Notification::make()
                                    ->title('Day Mismatch')
                                    ->body('Date must fall on ' . ucfirst($day))
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Select::make('recurring_months')
                        ->label('Recurring Duration')
                        ->options([
                            3 => '3 Months',
                            6 => '6 Months',
                            12 => '12 Months',
                        ])
                        ->visible(fn($get) => $get('is_recurring'))
                        ->required(fn($get) => $get('is_recurring'))
                        ->default(3)
                        ->helperText('How long should this recurring slot repeat?'),

                    \Filament\Schemas\Components\Grid::make(12)
                        ->schema([
                            TimePicker::make('start_time')->required()->columnSpan(6),
                            TimePicker::make('end_time')->required()->columnSpan(6),
                        ]),

                    \Filament\Schemas\Components\Grid::make(12)
                        ->schema([
                            TextInput::make('capacity')
                                ->label('Capacity')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->minValue(1)
                                ->columnSpan(4),

                            Select::make('consultation_type')
                                ->label('Mode')
                                ->options([
                                    'in-person' => 'In-Person',
                                    'video' => 'Video',
                                ])
                                ->default('in-person')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state === 'video') {
                                        $set('opd_type', null);
                                    }
                                })
                                ->columnSpan(4),

                            Select::make('opd_type')
                                ->label('OPD Type')
                                ->options([
                                    'general' => 'General',
                                    'private' => 'Private',
                                ])
                                ->default('general')
                                ->visible(fn($get) => $get('consultation_type') === 'in-person')
                                ->required(fn($get) => $get('consultation_type') === 'in-person')
                                ->columnSpan(4),
                        ]),

                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            TextInput::make('consultation_fee')
                                ->label('Fee (₹)')
                                ->numeric()
                                ->default(0),

                            TextInput::make('doctor_room')
                                ->label('Doctor Room')
                                ->placeholder('e.g., Room 101'),
                        ]),

                    Toggle::make('is_available')
                        ->label('Active')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger'),
                ])
                ->action(function (array $data) {
                    if ($data['is_recurring']) {
                        $startDate = Carbon::today();
                        $endDate = $startDate->copy()->addMonths($data['recurring_months']);

                        $slot = DoctorAvailability::create([
                            'doctor_id' => $data['doctor_id'],
                            'day_of_week' => $data['day_of_week'],
                            'date' => null,
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                            'capacity' => $data['capacity'],
                            'consultation_type' => $data['consultation_type'],
                            'opd_type' => $data['consultation_type'] === 'video' ? null : $data['opd_type'],
                            'consultation_fee' => $data['consultation_fee'] ?? 0,
                            'doctor_room' => $data['doctor_room'] ?? null,
                            'is_recurring' => true,
                            'is_available' => $data['is_available'] ?? true,
                            'recurring_start_date' => $startDate->format('Y-m-d'),
                            'recurring_end_date' => $endDate->format('Y-m-d'),
                        ]);

                        // Send push notification to doctor
                        if ($slot && $slot->doctor) {
                            \App\Services\NotificationService::notifyAvailabilityCreated($slot->doctor, [$slot->toArray()]);
                        }
                    } else {
                        // Ensure opd_type is null for video
                        if ($data['consultation_type'] === 'video') {
                            $data['opd_type'] = null;
                        }

                        $exists = DoctorAvailability::where('doctor_id', $data['doctor_id'])
                            ->where('date', $data['date'])
                            ->where('start_time', $data['start_time'])
                            ->where('end_time', $data['end_time'])
                            ->exists();

                        if (!$exists) {
                            $slot = DoctorAvailability::create($data);

                            // Send push notification to doctor
                            if ($slot && $slot->doctor) {
                                \App\Services\NotificationService::notifyAvailabilityCreated($slot->doctor, [$slot->toArray()]);
                            }
                        }
                    }
                    $this->loadScheduleByViewMode();

                    Notification::make()
                        ->title('Created successfully')
                        ->body('Created successfully.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function downloadTemplate(): StreamedResponse
    {
        $columns = [
            'Doctor Email',
            'Date (YYYY-MM-DD) or Day Name',
            'Start Time (HH:MM)',
            'End Time (HH:MM)',
            'Capacity',
            'Consultation Type (in-person/video)',
            'OPD Type (general/private)',
            'Doctor Room',
            'Consultation Fee',
            'Is Recurring (true/false)',
            'Recurring Start Date (YYYY-MM-DD)',
            'Recurring End Date (YYYY-MM-DD)',
            'Recurring Months'
        ];

        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            // Sample Row
            fputcsv($handle, [
                'doctor@example.com',
                'Monday',
                '09:00',
                '13:00',
                '10',
                'in-person',
                'general',
                'Room 101',
                '500',
                'true',
                date('Y-m-d'),
                date('Y-m-d', strtotime('+3 months')),
                '3',
                'Monday'
            ]);

            fclose($handle);
        }, 'doctor_slots_bulk_template.csv');
    }

    public function exportSlots(): StreamedResponse
    {
        $slots = DoctorAvailability::with('doctor.user')
            ->get();
        $columns = [
            'Doctor Email',
            'Date/Day',
            'Start Time',
            'End Time',
            'Capacity',
            'Consultation Type',
            'OPD Type',
            'Doctor Room',
            'Consultation Fee',
            'Is Recurring',
            'Recurring Start Date',
            'Recurring End Date',
            'Recurring Months',
            'Day Name'
        ];

        return response()->streamDownload(function () use ($slots, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($slots as $slot) {
                fputcsv($handle, [
                    $slot->doctor?->user?->email ?? 'N/A',
                    $slot->is_recurring ? ucfirst($slot->day_of_week) : $slot->date,
                    Carbon::parse($slot->start_time)->format('H:i'),
                    Carbon::parse($slot->end_time)->format('H:i'),
                    $slot->capacity,
                    $slot->consultation_type,
                    $slot->consultation_type === 'video' ? '' : ($slot->opd_type ?? 'general'),
                    $slot->doctor_room ?? 'N/A',
                    $slot->consultation_fee,
                    $slot->is_recurring ? 'true' : 'false',
                    $slot->recurring_start_date ? $slot->recurring_start_date->format('Y-m-d') : '',
                    $slot->recurring_end_date ? $slot->recurring_end_date->format('Y-m-d') : '',
                    $slot->recurring_months ?? 3,
                    ucfirst($slot->day_of_week)
                ]);
            }

            fclose($handle);
        }, 'doctor_slots_export_' . date('Y-m-d') . '.csv');
    }

    public $weekStart;
    public $weekEnd;
    public $days = [];
    public $monthDays = [];
    public $schedule = [];
    public $activeDay;
    public $viewMode = 'month';
    public $currentWeekLabel;

    public ?array $data = [];
    public $selectedDateSlots = [];
    public $selectedDateLabel;
    public $appointments = [];
    public $selectedTimeSlot = 'none';
    public $appointmentFilter = 'all';

    public function selectTimeSlot($timeSlot)
    {
        $this->selectedTimeSlot = $timeSlot;
    }

    public function getFilteredDateSlots(): array
    {
        if ($this->selectedTimeSlot === 'none') {
            return [];
        }

        if ($this->selectedTimeSlot === 'all') {
            return $this->selectedDateSlots;
        }

        return collect($this->selectedDateSlots)
            ->filter(function ($slot) {
                $time = ($slot['start'] && $slot['end']) ? $slot['start'] . ' - ' . $slot['end'] : null;
                return $time === $this->selectedTimeSlot;
            })
            ->values()
            ->toArray();
    }

    public function setAppointmentFilter($filter)
    {
        $this->appointmentFilter = $filter;
    }

    public function getAppointmentTypeCounts(): array
    {
        if ($this->selectedTimeSlot === 'none') {
            return ['all' => 0, 'online' => 0, 'external' => 0];
        }

        $filtered = collect($this->appointments);

        if ($this->selectedTimeSlot !== 'all') {
            $filtered = $filtered->filter(function ($appointment) {
                $appTime = ($appointment['start_time'] && $appointment['end_time']) ? $appointment['start_time'] . ' - ' . $appointment['end_time'] : null;
                if ($appTime === $this->selectedTimeSlot) {
                    return true;
                }

                $parts = explode(' - ', $this->selectedTimeSlot);
                if (count($parts) === 2) {
                    try {
                        $slotStart = Carbon::parse($parts[0]);
                        $slotEnd = Carbon::parse($parts[1]);
                        $appStart = Carbon::parse($appointment['start_time']);
                        return $appStart->gte($slotStart) && $appStart->lte($slotEnd);
                    } catch (\Exception $e) {
                        return false;
                    }
                }

                return false;
            });
        }

        $online = $filtered->filter(fn($app) => ($app['type'] ?? 'online') === 'online')->count();
        $external = $filtered->filter(fn($app) => ($app['type'] ?? 'online') === 'external')->count();

        return [
            'all' => $filtered->count(),
            'online' => $online,
            'external' => $external,
        ];
    }

    public function getFilteredAppointments(): array
    {
        if ($this->selectedTimeSlot === 'none') {
            return [];
        }

        $filtered = collect($this->appointments);

        // 1. Filter by time slot
        if ($this->selectedTimeSlot !== 'all') {
            $filtered = $filtered->filter(function ($appointment) {
                $appTime = ($appointment['start_time'] && $appointment['end_time']) ? $appointment['start_time'] . ' - ' . $appointment['end_time'] : null;
                if ($appTime === $this->selectedTimeSlot) {
                    return true;
                }

                $parts = explode(' - ', $this->selectedTimeSlot);
                if (count($parts) === 2) {
                    try {
                        $slotStart = Carbon::parse($parts[0]);
                        $slotEnd = Carbon::parse($parts[1]);
                        $appStart = Carbon::parse($appointment['start_time']);
                        return $appStart->gte($slotStart) && $appStart->lte($slotEnd);
                    } catch (\Exception $e) {
                        return false;
                    }
                }

                return false;
            });
        }

        // 2. Filter by type (online vs external)
        if ($this->appointmentFilter === 'online') {
            $filtered = $filtered->filter(fn($app) => ($app['type'] ?? 'online') === 'online');
        } elseif ($this->appointmentFilter === 'external') {
            $filtered = $filtered->filter(fn($app) => ($app['type'] ?? 'online') === 'external');
        }

        return $filtered->values()->toArray();
    }

    public function loadAppointmentsForDate($date)
    {
        $selectedDepartment = $this->getSelectedDepartment();
        $selectedDoctor = $this->getSelectedDoctor();
        $selectedOpdType = $this->getSelectedOpdType();

        // 1. Query Internal Appointments
        $appointmentsQuery = \App\Models\Appointment::with(['doctor.departments', 'patient', 'availability', 'payment'])
            ->whereHas('doctor', fn($query) => $query->active())
            ->whereDate('appointment_date', $date)
            ->where('status', AppointmentStatus::CONFIRMED->value)
            ->whereHas('payment', fn($query) => $query->where('status', \App\Enums\PaymentStatus::PAID->value));

        if ($selectedDepartment) {
            $appointmentsQuery->whereHas('doctor.departments', fn($q) => $q->where('departments.id', $selectedDepartment));
        }
        if ($selectedDoctor) {
            $appointmentsQuery->where('doctor_id', $selectedDoctor);
        }
        if ($selectedOpdType) {
            $appointmentsQuery->whereHas('availability', fn($q) => $q->where('opd_type', $selectedOpdType));
        }

        $internalList = $appointmentsQuery->get()->toBase()->map(function ($appointment) {
            $doctor = $appointment->doctor;
            $patient = $appointment->patient;

            $doctorName = $doctor
                ? trim(($doctor->title ? $doctor->title . ' ' : '') . $doctor->first_name . ' ' . $doctor->last_name)
                : 'Unknown';

            return [
                'id' => $appointment->id,
                'type' => 'online',
                'doctor_name' => $doctorName,
                'doctor_avatar' => ($doctor && $doctor->user && $doctor->user->avatar)
                    ? asset('storage/' . $doctor->user->avatar)
                    : asset('images/user-avatar.png'),
                'department' => $doctor && $doctor->departments ? $doctor->departments->pluck('name')->join(', ') : '',
                'specialization' => $doctor && $doctor->departments ? $doctor->departments->pluck('name')->join(', ') : '',
                'date' => $appointment->appointment_date,
                'start_time' => \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A'),
                'end_time' => \Carbon\Carbon::parse($appointment->availability?->end_time ?? $appointment->appointment_time)->format('g:i A'),
                'consultation_type' => ucfirst($appointment->consultation_type),
                'status' => $appointment->status,
                'patient_name' => $patient ? ($patient->first_name . ' ' . $patient->last_name) : '',
                'patient_email' => $patient?->email,
                'patient_phone' => $patient->mobile_no ?? null,
                'avatar' => $patient && $patient->user && $patient->user->avatar
                    ? storage_url($patient->user->avatar)
                    : asset('images/user-avatar.png'),
                'notes' => $appointment->notes,
                'reason' => $appointment->notes['reason'] ?? $appointment->notes['problem'] ?? null,
                'unit_no' => $patient?->existing_patient_id ?? '—',
            ];
        });

        // 2. Query External Bookings
        $externalQuery = \App\Models\ExternalBooking::with(['doctor.departments', 'availability'])
            ->whereHas('doctor', fn($query) => $query->active())
            ->whereDate('appointment_date', $date);

        if ($selectedDepartment) {
            $externalQuery->whereHas('doctor.departments', fn($q) => $q->where('departments.id', $selectedDepartment));
        }
        if ($selectedDoctor) {
            $externalQuery->where('doctor_id', $selectedDoctor);
        }
        if ($selectedOpdType) {
            $externalQuery->where('opd_type', $selectedOpdType);
        }

        $externalList = $externalQuery->get()->toBase()->map(function ($booking) {
            $doctor = $booking->doctor;
            $doctorName = $doctor
                ? trim(($doctor->title ? $doctor->title . ' ' : '') . $doctor->first_name . ' ' . $doctor->last_name)
                : ($booking->doctor_name ?? 'Unknown');

            return [
                'id' => $booking->id,
                'type' => 'external',
                'doctor_name' => $doctorName,
                'doctor_avatar' => ($doctor && $doctor->user && $doctor->user->avatar)
                    ? asset('storage/' . $doctor->user->avatar)
                    : asset('images/user-avatar.png'),
                'department' => $doctor && $doctor->departments ? $doctor->departments->pluck('name')->join(', ') : '',
                'specialization' => $doctor && $doctor->departments ? $doctor->departments->pluck('name')->join(', ') : '',
                'date' => $booking->appointment_date,
                'start_time' => \Carbon\Carbon::parse($booking->start_time)->format('g:i A'),
                'end_time' => \Carbon\Carbon::parse($booking->end_time ?? $booking->start_time)->format('g:i A'),
                'consultation_type' => ucfirst($booking->consultation_type),
                'status' => 'confirmed',
                'patient_name' => $booking->patient_name,
                'patient_email' => $booking->patient_email,
                'patient_phone' => $booking->mobile ?? null,
                'avatar' => asset('images/user-avatar.png'),
                'notes' => $booking->raw_payload,
                'reason' => 'External Import Booking',
                'unit_no' => $booking->patient_unit_number ?? '—',
            ];
        });

        // 3. Merge both collections
        $this->appointments = $internalList->merge($externalList)->toArray();
    }

    public function showDaySlots($date)
    {
        $this->selectedTimeSlot = 'none';
        $this->selectedDateLabel = Carbon::parse($date)->format('d F, Y');
        $this->loadAppointmentsForDate($date);

        $query = DoctorAvailability::with('doctor.departments', 'doctor.user', 'overrides')
            ->has('doctor')
            ->whereHas('doctor', fn($query) => $query->active())
            ->where(function ($q) use ($date) {
                $q->whereDate('date', $date)
                    ->orWhere(function ($q) {
                        $q->whereNull('date')
                            ->whereNotNull('day_of_week');
                    })
                    ->orWhere(function ($q) use ($date) {
                        $targetDow = Carbon::parse($date)->dayOfWeek + 1;
                        $q->where('is_recurring', true)
                            ->where(function ($sub) use ($date, $targetDow) {
                                $sub->where('day_of_week', strtolower(Carbon::parse($date)->format('l')))
                                    ->orWhereRaw("DAYOFWEEK(recurring_start_date) = ?", [$targetDow]);
                            })
                            ->where(function ($sub) use ($date) {
                                $sub->whereNull('recurring_start_date')
                                    ->orWhereDate('recurring_start_date', '<=', $date);
                            })
                            ->where(function ($sub) use ($date) {
                                $sub->whereNull('recurring_end_date')
                                    ->orWhereDate('recurring_end_date', '>=', $date);
                            });
                    });
            });

        $selectedDepartment = $this->getSelectedDepartment();
        $selectedDoctor = $this->getSelectedDoctor();
        $selectedOpdType = $this->getSelectedOpdType();

        if ($selectedDepartment) {
            $query->whereHas('doctor.departments', fn($q) => $q->where('departments.id', $selectedDepartment));
        }

        if ($selectedDoctor) {
            $query->where('doctor_id', $selectedDoctor);
        }

        if ($selectedOpdType) {
            $query->where('opd_type', $selectedOpdType);
        }

        $slots = app(DoctorAvailabilityService::class)
            ->expandSlots(
                $query->orderBy('start_time')->get(),
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay(),
                includePast: true,
                skipBlocked: false
            );

        $this->selectedDateSlots = $slots->map(function ($slot) use ($date) {
            $doctor = $slot->doctor;
            $doctorName = $doctor
                ? trim(($doctor->title ? $doctor->title . ' ' : '') . $doctor->first_name . ' ' . $doctor->last_name)
                : 'Unknown';
            $doctorId = $doctor?->id ?? null;
            $doctorSlug = $doctor?->slug ?? null;
            $departments = $doctor && $doctor->departments ? $doctor->departments->pluck('name')->join(', ') : '';
            $avatar = $doctor && $doctor->user && $doctor->user->avatar
                ? storage_url($doctor->user->avatar)
                : asset('images/user-avatar.png');

            $isRecurring = app(DoctorAvailabilityService::class)->isRecurringTemplate($slot);

            $recurringLabel = null;
            if ($isRecurring && $slot->recurring_start_date && $slot->recurring_end_date) {
                $recurringLabel =
                    Carbon::parse($slot->recurring_start_date)->format('d M Y')
                    . ' to ' .
                    Carbon::parse($slot->recurring_end_date)->format('d M Y');
            }

            $dateLabel = $slot->date ? Carbon::parse($slot->date)->format('d M Y') : null;

            $start = $slot->start_time ? Carbon::parse($slot->start_time)->format('g:i A') : null;
            $end = $slot->end_time ? Carbon::parse($slot->end_time)->format('g:i A') : null;
            $sortTime = $slot->start_time ? Carbon::parse($slot->start_time)->timestamp : 0;

            // Get status (Available vs Blocked)
            $service = app(DoctorAvailabilityService::class);
            $isBlocked = $service->isDateBlocked($slot, $date);
            $status = $isBlocked ? 'blocked' : 'active';

            // Get booked counts detail (Online vs External)
            $bookedDetail = app(SlotCapacityService::class)->bookedCountsDetail(
                doctorId: $slot->doctor_id,
                date: $date,
                startTime: $slot->start_time,
                availabilityId: $slot->id,
                consultationType: $slot->consultation_type
            );

            return [
                'id' => $slot->id,
                'doctor' => $doctorName,
                'doctor_id' => $doctorId,
                'doctor_slug' => $doctorSlug,
                'departments' => $departments,
                'avatar' => $avatar,
                'start' => $start,
                'end' => $end,
                'capacity' => $slot->capacity,
                'type' => ucfirst($slot->consultation_type),
                'consultation_type' => $slot->consultation_type,
                'opd_type' => $slot->consultation_type === 'video' ? null : ($slot->opd_type ?? 'general'),
                'is_recurring' => $isRecurring,
                'recurring_label' => $recurringLabel,
                'date_label' => $dateLabel,
                'sort_time' => $sortTime,
                'status' => $status,
                'internal_booked' => $bookedDetail['internal'] ?? 0,
                'external_booked' => $bookedDetail['external'] ?? 0,
                'total_booked' => $bookedDetail['total'] ?? 0,
                'source' => $slot->source ?? 'availability',
                'room' => $slot->doctor_room,
                'fee' => $slot->consultation_fee,
            ];
        })
            ->sortBy('sort_time')
            ->values()
            ->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('selectedDepartment')
                    ->label('Department')
                    ->placeholder('All Departments')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn() => $this->loadScheduleByViewMode()),

                \Filament\Forms\Components\Select::make('selectedDoctor')
                    ->label('Doctor')
                    ->placeholder('All Doctors')
                    ->options(
                        Doctor::active()->get()->mapWithKeys(
                            function ($doctor) {
                                return [
                                    $doctor->id =>
                                    trim(($doctor->title ? $doctor->title . ' ' : '') . $doctor->first_name . ' ' . $doctor->last_name)
                                ];
                            }
                        )
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn() => $this->loadScheduleByViewMode()),

                \Filament\Forms\Components\Select::make('selectedOpdType')
                    ->label('OPD Type')
                    ->placeholder('All OPD Types')
                    ->options([
                        'general' => 'General',
                        'private' => 'Private',
                    ])
                    ->live()
                    ->afterStateUpdated(fn() => $this->loadScheduleByViewMode()),
            ])
            ->statePath('data')
            ->columns(3);
    }

    protected function loadScheduleByViewMode(): void
    {
        if ($this->viewMode === 'month') {
            $this->loadMonthView();
        } elseif ($this->viewMode === 'day') {
            $this->loadScheduleForDay();
        } else {
            $this->loadSchedule();
        }
    }

    protected function getSelectedDepartment()
    {
        return $this->data['selectedDepartment'] ?? null;
    }

    protected function getSelectedDoctor()
    {
        return $this->data['selectedDoctor'] ?? null;
    }

    protected function getSelectedOpdType()
    {
        return $this->data['selectedOpdType'] ?? null;
    }

    public function changeView($mode)
    {
        $this->viewMode = $mode;

        if ($mode === 'month') {
            if (!$this->currentMonthStart) {
                $this->currentMonthStart = Carbon::now()->startOfMonth();
            }
            $this->loadMonthView();
        } elseif ($mode === 'day') {
            $this->loadScheduleForDay();
        } else {
            $this->loadSchedule();
        }
    }

    public $currentMonthStart;
    public $currentMonthLabel;

    public function previousMonth()
    {
        $this->currentMonthStart = $this->currentMonthStart->subMonth();
        $this->showDaySlots($this->currentMonthStart->format('Y-m-d'));
        $this->loadMonthView();
    }

    public function nextMonth()
    {
        $this->currentMonthStart = $this->currentMonthStart->addMonth();
        $this->showDaySlots($this->currentMonthStart->format('Y-m-d'));
        $this->loadMonthView();
    }

    private function loadMonthView()
    {
        if (!$this->currentMonthStart) {
            $this->currentMonthStart = Carbon::now()->startOfMonth();
        }

        $this->currentMonthLabel = $this->currentMonthStart->format('F Y');

        $startOfMonth = $this->currentMonthStart->copy();
        $endOfMonth = $this->currentMonthStart->copy()->endOfMonth();

        $calendarStart = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];

        // Build calendar grid
        $cursor = $calendarStart->copy();
        while ($cursor->lte($calendarEnd)) {
            $isCurrentMonth = $cursor->month === $startOfMonth->month && $cursor->year === $startOfMonth->year;
            $isToday = $cursor->isToday();

            $days[$cursor->format('Y-m-d')] = [
                'date' => $cursor->copy(),
                'events' => [],
                'is_current_month' => $isCurrentMonth,
                'is_today' => $isToday,
            ];

            $cursor->addDay();
        }

        // Fetch all events including recurring ones
        $query = DoctorAvailability::with('doctor', 'overrides')
            ->has('doctor')
            ->whereHas('doctor', fn($query) => $query->active())
            ->where('is_available', true)
            ->where(function ($query) use ($calendarStart, $calendarEnd) {
                $query->whereBetween('date', [$calendarStart, $calendarEnd])
                    ->orWhere(function ($q) {
                        $q->whereNull('date')
                            ->whereNotNull('day_of_week');
                    })
                    ->orWhere(function ($q) use ($calendarStart, $calendarEnd) {
                        $q->where('is_recurring', true)
                            ->where(function ($sub) use ($calendarEnd) {
                                $sub->whereNull('recurring_start_date')
                                    ->orWhereDate('recurring_start_date', '<=', $calendarEnd);
                            })
                            ->where(function ($sub) use ($calendarStart) {
                                $sub->whereNull('recurring_end_date')
                                    ->orWhereDate('recurring_end_date', '>=', $calendarStart);
                            });
                    });
            });

        $selectedDepartment = $this->getSelectedDepartment();
        $selectedDoctor = $this->getSelectedDoctor();
        $selectedOpdType = $this->getSelectedOpdType();

        if ($selectedDepartment) {
            $query->whereHas('doctor.departments', fn($q) => $q->where('departments.id', $selectedDepartment));
        }

        if ($selectedDoctor) {
            $query->where('doctor_id', $selectedDoctor);
        }

        if ($selectedOpdType) {
            $query->where('opd_type', $selectedOpdType);
        }

        $events = $this->expandCalendarSlotsUntilEndTime(
            $query->get(),
            $calendarStart->copy()->startOfDay(),
            $calendarEnd->copy()->endOfDay()
        );

        foreach ($events as $event) {
            $doctor = $event->doctor;
            $doctorName = $doctor
                ? trim(($doctor->title ? $doctor->title . ' ' : '') . $doctor->first_name . ' ' . $doctor->last_name)
                : 'Unknown';

            $dayKey = Carbon::parse($event->date)->format('Y-m-d');
            if (isset($days[$dayKey])) {
                $days[$dayKey]['events'][] = [
                    'id' => $event->id,
                    'doctor_name' => $doctorName,
                    'start_time' => Carbon::parse($event->start_time)->format('g:i A'),
                    'end_time' => Carbon::parse($event->end_time)->format('g:i A'),
                ];
            }
        }

        $this->monthDays = array_values($days);
    }

    public function mount()
    {
        $module = static::$slug ?? strtolower(class_basename(static::class));
        if (! check_permission(["{$module}.view", "{$module}.view_any"])) {
            abort(403);
        }

        $today = now()->format('Y-m-d');
        $this->showDaySlots($today);
        $this->activeDay = strtolower(Carbon::now()->format('l'));
        $this->currentMonthStart = Carbon::now()->startOfMonth();
        $this->form->fill();
        $this->goToToday();
        $this->loadMonthView();
    }

    public function previousDay()
    {
        if (!isset($this->days[$this->activeDay])) {
            return;
        }

        $currentDate = $this->days[$this->activeDay];
        $previousDate = $currentDate->copy()->subDay();
        $this->activeDay = strtolower($previousDate->format('l'));

        $this->showDaySlots($previousDate);

        if ($previousDate->lt($this->weekStart)) {
            $this->previousWeek();
        } else {
            $this->loadScheduleForDay();
        }
    }

    public function nextDay()
    {
        if (!isset($this->days[$this->activeDay])) {
            return;
        }

        $currentDate = $this->days[$this->activeDay];
        $nextDate = $currentDate->copy()->addDay();
        $this->activeDay = strtolower($nextDate->format('l'));

        $this->showDaySlots($nextDate);

        if ($nextDate->gt($this->weekEnd)) {
            $this->nextWeek();
        } else {
            $this->loadScheduleForDay();
        }
    }

    public function goToToday()
    {
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $this->weekEnd = $this->weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $this->currentWeekLabel = $this->weekStart->format('M d') . ' - ' . $this->weekEnd->format('M d, Y');
        $this->setDays();
    }

    public function previousWeek()
    {
        $this->weekStart = $this->weekStart->subWeek();
        $this->weekEnd = $this->weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $this->currentWeekLabel = $this->weekStart->format('M d') . ' - ' . $this->weekEnd->format('M d, Y');
        $this->setDays();
        if (isset($this->days[$this->activeDay])) {
            $this->showDaySlots($this->days[$this->activeDay]->format('Y-m-d'));
        }
        $this->loadSchedule();
    }

    public function nextWeek()
    {
        $this->weekStart = $this->weekStart->addWeek();
        $this->weekEnd = $this->weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $this->currentWeekLabel = $this->weekStart->format('M d') . ' - ' . $this->weekEnd->format('M d, Y');
        $this->setDays();
        if (isset($this->days[$this->activeDay])) {
            $this->showDaySlots($this->days[$this->activeDay]->format('Y-m-d'));
        }
        $this->loadSchedule();
    }

    private function setDays()
    {
        $this->days = [];
        $current = $this->weekStart->copy();
        while ($current->lte($this->weekEnd)) {
            $this->days[strtolower($current->format('l'))] = $current->copy();
            $current->addDay();
        }
    }

    public function changeDay($dayName)
    {
        $this->activeDay = strtolower($dayName);
        $this->loadScheduleForDay();
    }

    private function loadSchedule()
    {
        $schedule = [];

        $start = $this->weekStart->copy()->startOfDay();
        $end = $this->weekEnd->copy()->endOfDay();

        $query = DoctorAvailability::with(['doctor.departments', 'doctor.user', 'overrides'])
            ->has('doctor')
            ->whereHas('doctor', fn($query) => $query->active())
            ->where('is_available', true)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start, $end])
                    ->orWhere(function ($q) {
                        $q->whereNull('date')
                            ->whereNotNull('day_of_week');
                    })
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('is_recurring', true)
                            ->where(function ($sub) use ($end) {
                                $sub->whereNull('recurring_start_date')
                                    ->orWhereDate('recurring_start_date', '<=', $end);
                            })
                            ->where(function ($sub) use ($start) {
                                $sub->whereNull('recurring_end_date')
                                    ->orWhereDate('recurring_end_date', '>=', $start);
                            });
                    });
            });

        $selectedDepartment = $this->getSelectedDepartment();
        $selectedDoctor = $this->getSelectedDoctor();
        $selectedOpdType = $this->getSelectedOpdType();

        if ($selectedDepartment) {
            $query->whereHas('doctor.departments', fn($q) => $q->where('departments.id', $selectedDepartment));
        }

        if ($selectedDoctor) {
            $query->where('doctor_id', $selectedDoctor);
        }

        if ($selectedOpdType) {
            $query->where('opd_type', $selectedOpdType);
        }

        $slots = $this->expandCalendarSlotsUntilEndTime($query->get(), $start, $end);

        foreach ($slots as $slot) {
            $doctor = $slot->doctor;
            $doctorName = $doctor
                ? trim(($doctor->title ? $doctor->title . ' ' : '') . $doctor->first_name . ' ' . $doctor->last_name)
                : 'Unknown';
            $departments = $doctor && $doctor->departments ? $doctor->departments->pluck('name')->join(', ') : '';
            $avatar = $doctor && $doctor->user && $doctor->user->avatar
                ? storage_url($doctor->user->avatar)
                : asset('images/user-avatar.png');

            $dayKey = strtolower(Carbon::parse($slot->date)->format('l'));
            $timeKey = Carbon::parse($slot->start_time)->format('H:i');

            $schedule[$dayKey][$timeKey][] = [
                'doctor_name' => $doctorName,
                'departments' => $departments,
                'start_time' => Carbon::parse($slot->start_time)->format('g:i A'),
                'end_time' => Carbon::parse($slot->end_time)->format('g:i A'),
                'avatar' => $avatar,
                'consultation_type' => $slot->consultation_type,
                'opd_type' => $slot->consultation_type === 'video' ? null : ($slot->opd_type ?? 'general'),
                'source' => $slot->source ?? 'availability',
                'availability_override_id' => $slot->override_id ?? null,
            ];
        }

        $this->schedule = $schedule;
    }

    private function loadScheduleForDay()
    {
        $selectedDayDate = $this->days[$this->activeDay];
        $selectedDayLower = strtolower($selectedDayDate->format('l'));

        $query = DoctorAvailability::with(['doctor.departments', 'doctor.user', 'overrides'])
            ->has('doctor')
            ->whereHas('doctor', fn($query) => $query->active())
            ->where('is_available', true)
            ->where(function ($q) use ($selectedDayDate, $selectedDayLower) {
                $q->whereDate('date', $selectedDayDate)
                    ->orWhere(function ($q) {
                        $q->whereNull('date')
                            ->whereNotNull('day_of_week');
                    })
                    ->orWhere(function ($q) use ($selectedDayDate, $selectedDayLower) {
                        $targetDow = $selectedDayDate->dayOfWeek + 1;
                        $q->where('is_recurring', true)
                            ->where(function ($sub) use ($selectedDayLower, $targetDow) {
                                $sub->where('day_of_week', $selectedDayLower)
                                    ->orWhereRaw("DAYOFWEEK(recurring_start_date) = ?", [$targetDow]);
                            })
                            ->where(function ($sub) use ($selectedDayDate) {
                                $sub->whereNull('recurring_start_date')
                                    ->orWhereDate('recurring_start_date', '<=', $selectedDayDate);
                            })
                            ->where(function ($sub) use ($selectedDayDate) {
                                $sub->whereNull('recurring_end_date')
                                    ->orWhereDate('recurring_end_date', '>=', $selectedDayDate);
                            });
                    });
            });

        $selectedDepartment = $this->getSelectedDepartment();
        $selectedDoctor = $this->getSelectedDoctor();
        $selectedOpdType = $this->getSelectedOpdType();

        if ($selectedDepartment) {
            $query->whereHas('doctor.departments', fn($q) => $q->where('departments.id', $selectedDepartment));
        }

        if ($selectedDoctor) {
            $query->where('doctor_id', $selectedDoctor);
        }

        if ($selectedOpdType) {
            $query->where('opd_type', $selectedOpdType);
        }

        $slots = $this->expandCalendarSlotsUntilEndTime(
            $query->get(),
            $selectedDayDate->copy()->startOfDay(),
            $selectedDayDate->copy()->endOfDay()
        );

        $daySchedule = [];
        foreach ($slots as $slot) {
            $doctor = $slot->doctor;
            $doctorName = $doctor
                ? trim(($doctor->title ? $doctor->title . ' ' : '') . $doctor->first_name . ' ' . $doctor->last_name)
                : 'Unknown';
            $departments = $doctor && $doctor->departments ? $doctor->departments->pluck('name')->join(', ') : '';
            $avatar = $doctor && $doctor->user && $doctor->user->avatar
                ? storage_url($doctor->user->avatar)
                : asset('images/user-avatar.png');

            $timeKey = Carbon::parse($slot->start_time)->format('H:i');
            $daySchedule[$timeKey][] = [
                'doctor_name' => $doctorName,
                'departments' => $departments,
                'start_time' => Carbon::parse($slot->start_time)->format('g:i A'),
                'end_time' => Carbon::parse($slot->end_time)->format('g:i A'),
                'avatar' => $avatar,
                'consultation_type' => $slot->consultation_type,
                'opd_type' => $slot->consultation_type === 'video' ? null : ($slot->opd_type ?? 'general'),
                'source' => $slot->source ?? 'availability',
                'availability_override_id' => $slot->override_id ?? null,
            ];
        }

        $this->schedule[$selectedDayLower] = $daySchedule;
    }

    public function getDepartmentsProperty()
    {
        return Department::all();
    }

    public function getDoctorsProperty()
    {
        return Doctor::active()->get();
    }

    private function expandCalendarSlotsUntilEndTime(iterable $availabilities, Carbon $startDate, Carbon $endDate)
    {
        $now = Carbon::now();

        return app(DoctorAvailabilityService::class)
            ->expandSlots($availabilities, $startDate, $endDate, includePast: true)
            ->filter(function ($slot) use ($now) {
                $date = Carbon::parse($slot->date)->toDateString();
                $time = $slot->end_time ?: $slot->start_time;
                $timeString = $time instanceof Carbon ? $time->format('H:i:s') : $time;
                $slotEnd = Carbon::parse($date . ' ' . $timeString);

                if ($slot->start_time) {
                    $startTime = $slot->start_time instanceof Carbon
                        ? $slot->start_time->format('H:i:s')
                        : $slot->start_time;
                    $slotStart = Carbon::parse($date . ' ' . $startTime);

                    if ($slotEnd->lessThan($slotStart)) {
                        $slotEnd->addDay();
                    }
                }

                return $slotEnd->greaterThanOrEqualTo($now);
            });
    }

    protected function getViewData(): array
    {
        return [
            'monthDays' => $this->monthDays,
            'days' => $this->days,
            'schedule' => $this->schedule,
            'viewMode' => $this->viewMode,
            'weekStart' => $this->weekStart,
            'weekEnd' => $this->weekEnd,
            'activeDay' => $this->activeDay,
            'currentMonthLabel' => $this->currentMonthLabel,
            'currentWeekLabel' => $this->currentWeekLabel,
        ];
    }
}