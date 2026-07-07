<?php

namespace App\Filament\Resources\DoctorReplacements\Schemas;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Forms\Components\DateRangePicker;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Carbon\Carbon;

class DoctorReplacementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Replacement Details')
                    ->description('Select the doctor who needs replacement and their replacement doctor')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Select::make('original_doctor_id')
                            ->label('Original Doctor')
                            ->options(function () {
                                return Doctor::query()
                                    ->with('user')
                                    ->get()
                                    ->mapWithKeys(function ($doctor) {
                                        return [$doctor->id => "{$doctor->first_name} {$doctor->last_name}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('replacement_doctor_id', null))
                            ->helperText('Select the doctor who will be on leave or unavailable'),
                        Select::make('replacement_doctor_id')
                            ->label('Replacement Doctor')
                            ->options(function ($get) {
                                $originalDoctorId = $get('original_doctor_id');
                                $query = Doctor::query()->with('user');

                                if ($originalDoctorId) {
                                    $query->where('id', '!=', $originalDoctorId);
                                }

                                return $query->get()
                                    ->mapWithKeys(function ($doctor) {
                                        return [$doctor->id => "{$doctor->first_name} {$doctor->last_name}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn($get) => !$get('original_doctor_id'))
                            ->helperText('Select the doctor who will replace the original doctor'),
                        Select::make('replacement_type')
                            ->label('Replacement Type')
                            ->options([
                                'all' => 'All Appointments (Date Range)',
                                'permanent' => 'Permanent Replacement',
                            ])
                            ->required()
                            ->live()
                            ->default('all')
                            ->helperText('Select "All Appointments (Date Range)" to replace doctor for a custom date range. Any availability in that range will be replaced.')
                            ->columnSpanFull(),
                        Checkbox::make('transfer_availability')
                            ->label('Transfer Availability Slots')
                            ->default(true)
                            ->helperText('When enabled, availability slots from original doctor will be replaced by the replacement doctor for the selected period.')
                            ->visible(fn($get) => in_array($get('replacement_type'), ['all', 'permanent']))
                            ->columnSpanFull(),
                        TextInput::make('replacement_room')
                            ->label('Replacement Doctor Room')
                            ->placeholder('e.g., Room 101')
                            ->required()
                            ->helperText('Room where replacement doctor will consult patients')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Date Range & Leave Period')
                    ->description('Select the date range for replacement and leave period. Any availability in this range will be replaced.')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        DateRangePicker::make('date_range')
                            ->label('Leave Period')
                            ->startDateField('start_date')
                            ->endDateField('end_date')
                            ->minDate(now())
                            ->required()
                            ->live()
                            ->helperText('Select the date range for the leave period. You can use presets or select custom dates.')
                            ->columnSpanFull()
                            ->visible(fn($get) => in_array($get('replacement_type'), ['all', 'permanent'])),
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->required()
                            ->live()
                            ->minDate(now())
                            ->hidden()
                            ->dehydrated()
                            ->visible(fn($get) => in_array($get('replacement_type'), ['all', 'permanent'])),
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->required()
                            ->live()
                            ->minDate(fn($get) => $get('start_date') ? Carbon::parse($get('start_date')) : now())
                            ->hidden()
                            ->dehydrated()
                            ->visible(fn($get) => in_array($get('replacement_type'), ['all', 'permanent'])),
                    ])
                    ->visible(fn($get) => in_array($get('replacement_type'), ['all', 'permanent']))
                    ->columnSpanFull(),
                Section::make('Select Availability Slots to Replace')
                    ->description('Select specific availability slots to replace. Leave empty to replace all slots in the date range.')
                    ->icon('heroicon-o-clock')
                    ->heading('Availability Slots Selection')
                    ->schema([
                        Select::make('selected_availability_ids')
                            ->label('Availability Slots')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function ($get) {
                                $originalDoctorId = $get('original_doctor_id');
                                $startDate = $get('start_date');
                                $endDate = $get('end_date');

                                // Debug: Check if dates are being received
                                if (!$originalDoctorId) {
                                    return [];
                                }

                                if (!$startDate || !$endDate) {
                                    return [];
                                }

                                try {
                                    $start = Carbon::parse($startDate)->startOfDay();
                                    $end = Carbon::parse($endDate)->endOfDay();
                                } catch (\Exception $e) {
                                    return [];
                                }

                                // Get all potential availabilities (recurring and non-recurring)
                                $availabilities = DoctorAvailability::where('doctor_id', $originalDoctorId)
                                    ->where('is_available', true)
                                    ->where(function ($q) use ($start, $end) {
                                        // Non-recurring slots within date range
                                        $q->where(function ($sq) use ($start, $end) {
                                            $sq->where('is_recurring', false)
                                                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                                        })
                                            // Recurring slots that might occur in the date range
                                            ->orWhere(function ($sq) use ($start, $end) {
                                                $sq->where('is_recurring', true)
                                                    ->where(function ($q) use ($start, $end) {
                                                        $q->whereNull('recurring_end_date')
                                                            ->orWhere('recurring_end_date', '>=', $start->format('Y-m-d'));
                                                    })
                                                    ->where('recurring_start_date', '<=', $end->format('Y-m-d'));
                                            });
                                    })
                                    ->orderBy('start_time')
                                    ->get();

                                $options = [];

                                foreach ($availabilities as $availability) {
                                    if ($availability->is_recurring) {
                                        // For recurring slots, check each date in the range
                                        $recurringStart = $availability->recurring_start_date
                                            ? Carbon::parse($availability->recurring_start_date)->startOfDay()
                                            : $start;
                                        $recurringEnd = $availability->recurring_end_date
                                            ? Carbon::parse($availability->recurring_end_date)->endOfDay()
                                            : $end;

                                        // Find the effective date range (intersection of recurring period and selected range)
                                        $effectiveStart = $recurringStart->gt($start) ? $recurringStart : $start;
                                        $effectiveEnd = $recurringEnd->lt($end) ? $recurringEnd : $end;

                                        // Check if this recurring slot actually occurs on any date in the selected range
                                        $dayName = strtolower($availability->day_of_week);
                                        $current = $effectiveStart->copy();
                                        $hasOccurrence = false;

                                        while ($current->lte($effectiveEnd)) {
                                            if (strtolower($current->format('l')) === $dayName) {
                                                $hasOccurrence = true;
                                                break;
                                            }
                                            $current->addDay();
                                        }

                                        // Only include if it actually occurs in the date range
                                        if ($hasOccurrence) {
                                            // Count occurrences and show first/last dates
                                            $firstOccurrence = $effectiveStart->copy();
                                            while ($firstOccurrence->lte($effectiveEnd)) {
                                                if (strtolower($firstOccurrence->format('l')) === $dayName) {
                                                    break;
                                                }
                                                $firstOccurrence->addDay();
                                            }

                                            $lastOccurrence = $effectiveEnd->copy();
                                            while ($lastOccurrence->gte($effectiveStart)) {
                                                if (strtolower($lastOccurrence->format('l')) === $dayName) {
                                                    break;
                                                }
                                                $lastOccurrence->subDay();
                                            }

                                            $dateLabel = ucfirst($availability->day_of_week) . ' (' .
                                                $firstOccurrence->format('M d, Y') . ' - ' .
                                                $lastOccurrence->format('M d, Y') . ')';

                                    $timeLabel = Carbon::parse($availability->start_time)->format('g:i A') . ' - ' .
                                        Carbon::parse($availability->end_time)->format('g:i A');

                                    $roomLabel = $availability->doctor_room ? ' • ' . $availability->doctor_room : '';
                                    $typeLabel = ucfirst($availability->consultation_type);

                                    $label = "{$dateLabel} | {$timeLabel}{$roomLabel} | {$typeLabel}";

                                    $options[$availability->id] = $label;
                                        }
                                    } else {
                                        // Non-recurring slot - already filtered by date range
                                        $dateLabel = Carbon::parse($availability->date)->format('M d, Y');
                                        $timeLabel = Carbon::parse($availability->start_time)->format('g:i A') . ' - ' .
                                            Carbon::parse($availability->end_time)->format('g:i A');
                                        $roomLabel = $availability->doctor_room ? ' • ' . $availability->doctor_room : '';
                                        $typeLabel = ucfirst($availability->consultation_type);

                                        $label = "{$dateLabel} | {$timeLabel}{$roomLabel} | {$typeLabel}";

                                        $options[$availability->id] = $label;
                                    }
                                }

                                return $options;
                            })
                            ->helperText('Select specific slots to replace, or leave empty to replace all slots in the date range')
                            ->placeholder('Select availability slots...')
                            ->loadingMessage('Loading availability slots...')
                            ->noSearchResultsMessage('No availability slots found in the selected date range.')
                            ->getOptionLabelUsing(function ($value, $get) {
                                $originalDoctorId = $get('original_doctor_id');
                                $startDate = $get('start_date');
                                $endDate = $get('end_date');

                                if (!$originalDoctorId || !$startDate || !$endDate) {
                                    return '';
                                }

                                $availability = DoctorAvailability::find($value);
                                if (!$availability) {
                                    return '';
                                }

                                if ($availability->is_recurring) {
                                    return ucfirst($availability->day_of_week) . ' (Recurring)';
                                }

                                return Carbon::parse($availability->date)->format('M d, Y');
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($get) => $get('original_doctor_id') && $get('start_date') && $get('end_date') && in_array($get('replacement_type'), ['all', 'permanent']))
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),
                Section::make('Doctor Schedule Preview')
                    ->description('Preview of the original doctor\'s availability in the selected date range')
                    ->icon('heroicon-o-calendar-days')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Placeholder::make('schedule_preview')
                            ->label('')
                            ->content(function ($get) {
                                $originalDoctorId = $get('original_doctor_id');
                                $startDate = $get('start_date');
                                $endDate = $get('end_date');

                                if (!$originalDoctorId || !$startDate || !$endDate) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">Select doctor and date range to preview schedule.</p>');
                                }

                                $doctor = Doctor::find($originalDoctorId);
                                if (!$doctor) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-sm text-red-500">Doctor not found.</p>');
                                }

                                $start = Carbon::parse($startDate)->startOfDay();
                                $end = Carbon::parse($endDate)->endOfDay();

                                // Get all potential availabilities
                                $allAvailabilities = DoctorAvailability::where('doctor_id', $originalDoctorId)
                                    ->where('is_available', true)
                                    ->where(function ($q) use ($start, $end) {
                                        // Non-recurring slots within date range
                                        $q->where(function ($sq) use ($start, $end) {
                                            $sq->where('is_recurring', false)
                                                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                                        })
                                            // Recurring slots that might occur in the date range
                                            ->orWhere(function ($sq) use ($start, $end) {
                                                $sq->where('is_recurring', true)
                                                    ->where(function ($q) use ($start, $end) {
                                                        $q->whereNull('recurring_end_date')
                                                            ->orWhere('recurring_end_date', '>=', $start->format('Y-m-d'));
                                                    })
                                                    ->where('recurring_start_date', '<=', $end->format('Y-m-d'));
                                            });
                                    })
                                    ->orderBy('start_time')
                                    ->get();

                                // Filter to only include slots that actually occur on dates in the selected range
                                $availabilities = collect();

                                foreach ($allAvailabilities as $availability) {
                                    if ($availability->is_recurring) {
                                        // Check if this recurring slot actually occurs on any date in the selected range
                                        $recurringStart = $availability->recurring_start_date
                                            ? Carbon::parse($availability->recurring_start_date)->startOfDay()
                                            : $start;
                                        $recurringEnd = $availability->recurring_end_date
                                            ? Carbon::parse($availability->recurring_end_date)->endOfDay()
                                            : $end;

                                        // Find the effective date range (intersection)
                                        $effectiveStart = $recurringStart->gt($start) ? $recurringStart : $start;
                                        $effectiveEnd = $recurringEnd->lt($end) ? $recurringEnd : $end;

                                        // Check if this recurring slot occurs on any date in the range
                                        $dayName = strtolower($availability->day_of_week);
                                        $current = $effectiveStart->copy();

                                        while ($current->lte($effectiveEnd)) {
                                            if (strtolower($current->format('l')) === $dayName) {
                                                // This recurring slot occurs on at least one date in the range
                                                $availabilities->push($availability);
                                                break;
                                            }
                                            $current->addDay();
                                        }
                                    } else {
                                        // Non-recurring slot - already filtered by date range
                                        $availabilities->push($availability);
                                    }
                                }

                                if ($availabilities->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"><p class="text-sm text-gray-500 dark:text-gray-400">No availability found in the selected date range.</p></div>');
                                }

                                $html = '<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">';
                                $html .= '<div class="mb-3"><h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">' . htmlspecialchars($doctor->first_name) . ' ' . htmlspecialchars($doctor->last_name) . ' - Schedule Preview</h4>';
                                $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">' . $start->format('M d, Y') . ' to ' . $end->format('M d, Y') . '</p></div>';
                                $html .= '<div class="space-y-3 max-h-96 overflow-y-auto">';

                                // Separate recurring and non-recurring slots
                                $recurringSlots = [];
                                $nonRecurringSlots = [];

                                foreach ($availabilities as $availability) {
                                    if ($availability->is_recurring) {
                                        // Group recurring slots by availability ID to avoid duplicates
                                        $key = $availability->id;
                                        if (!isset($recurringSlots[$key])) {
                                            $recurringSlots[$key] = $availability;
                                        }
                                    } else {
                                        $dateKey = $availability->date;
                                        if (!isset($nonRecurringSlots[$dateKey])) {
                                            $nonRecurringSlots[$dateKey] = [];
                                        }
                                        $nonRecurringSlots[$dateKey][] = $availability;
                                    }
                                }

                                // Display recurring slots first - show actual occurrence dates
                                if (!empty($recurringSlots)) {
                                    foreach ($recurringSlots as $slot) {
                                        $dayName = strtolower($slot->day_of_week);

                                        // Calculate effective date range for this recurring slot
                                        $recurringStart = $slot->recurring_start_date
                                            ? Carbon::parse($slot->recurring_start_date)->startOfDay()
                                            : $start;
                                        $recurringEnd = $slot->recurring_end_date
                                            ? Carbon::parse($slot->recurring_end_date)->endOfDay()
                                            : $end;

                                        // Find intersection of recurring period and selected range
                                        $effectiveStart = $recurringStart->gt($start) ? $recurringStart : $start;
                                        $effectiveEnd = $recurringEnd->lt($end) ? $recurringEnd : $end;

                                        // Find first and last occurrence dates
                                        $firstOccurrence = $effectiveStart->copy();
                                        while ($firstOccurrence->lte($effectiveEnd)) {
                                            if (strtolower($firstOccurrence->format('l')) === $dayName) {
                                                break;
                                            }
                                            $firstOccurrence->addDay();
                                        }

                                        $lastOccurrence = $effectiveEnd->copy();
                                        while ($lastOccurrence->gte($effectiveStart)) {
                                            if (strtolower($lastOccurrence->format('l')) === $dayName) {
                                                break;
                                    }
                                            $lastOccurrence->subDay();
                                        }

                                        // Only show if there's at least one occurrence
                                        if ($firstOccurrence->lte($effectiveEnd) && $lastOccurrence->gte($effectiveStart)) {
                                            $html .= '<div class="border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">';
                                            $html .= '<div class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2">';
                                            $html .= htmlspecialchars(ucfirst($slot->day_of_week)) . ', ' .
                                                htmlspecialchars($firstOccurrence->format('M d, Y')) . ' - ' .
                                                htmlspecialchars($lastOccurrence->format('M d, Y'));
                                            $html .= '</div>';
                                            $html .= '<div class="space-y-1">';
                                            $html .= '<div class="flex items-center justify-between text-xs bg-gray-50 dark:bg-gray-700/50 rounded px-2 py-1.5">';
                                            $html .= '<div class="flex items-center gap-2">';
                                            $html .= '<span class="text-gray-600 dark:text-gray-300">' .
                                                Carbon::parse($slot->start_time)->format('g:i A') . ' - ' .
                                                Carbon::parse($slot->end_time)->format('g:i A') . '</span>';
                                            if ($slot->doctor_room) {
                                                $html .= '<span class="text-gray-500 dark:text-gray-400">• ' . htmlspecialchars($slot->doctor_room) . '</span>';
                                            }
                                            $html .= '</div>';
                                            $html .= '<div class="flex items-center gap-2">';
                                            $html .= '<span class="px-2 py-0.5 rounded text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">' .
                                                htmlspecialchars(ucfirst($slot->consultation_type)) . '</span>';
                                            $html .= '<span class="px-2 py-0.5 rounded text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">Recurring</span>';
                                            $html .= '</div></div>';
                                            $html .= '</div></div>';
                                        }
                                    }
                                }

                                // Display non-recurring slots
                                ksort($nonRecurringSlots);
                                foreach ($nonRecurringSlots as $date => $slots) {
                                    $dateObj = Carbon::parse($date);
                                    $html .= '<div class="border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">';
                                    $html .= '<div class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2">' .
                                        htmlspecialchars($dateObj->format('l, M d, Y')) . '</div>';
                                    $html .= '<div class="space-y-1">';
                                    foreach ($slots as $slot) {
                                        $html .= '<div class="flex items-center justify-between text-xs bg-gray-50 dark:bg-gray-700/50 rounded px-2 py-1.5">';
                                        $html .= '<div class="flex items-center gap-2">';
                                        $html .= '<span class="text-gray-600 dark:text-gray-300">' .
                                            Carbon::parse($slot->start_time)->format('g:i A') . ' - ' .
                                            Carbon::parse($slot->end_time)->format('g:i A') . '</span>';
                                        if ($slot->doctor_room) {
                                            $html .= '<span class="text-gray-500 dark:text-gray-400">• ' . htmlspecialchars($slot->doctor_room) . '</span>';
                                        }
                                        $html .= '</div>';
                                        $html .= '<div class="flex items-center gap-2">';
                                        $html .= '<span class="px-2 py-0.5 rounded text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">' .
                                            htmlspecialchars(ucfirst($slot->consultation_type)) . '</span>';
                                        $html .= '</div></div>';
                                    }
                                    $html .= '</div></div>';
                                }

                                $html .= '</div>';
                                $html .= '<div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">';

                                // Calculate total occurrences (including recurring slots expanded)
                                $totalOccurrences = 0;
                                foreach ($recurringSlots as $slot) {
                                    $dayName = strtolower($slot->day_of_week);

                                    // Calculate effective date range for this recurring slot
                                    $recurringStart = $slot->recurring_start_date
                                        ? Carbon::parse($slot->recurring_start_date)->startOfDay()
                                        : $start;
                                    $recurringEnd = $slot->recurring_end_date
                                        ? Carbon::parse($slot->recurring_end_date)->endOfDay()
                                        : $end;

                                    // Find intersection of recurring period and selected range
                                    $effectiveStart = $recurringStart->gt($start) ? $recurringStart : $start;
                                    $effectiveEnd = $recurringEnd->lt($end) ? $recurringEnd : $end;

                                    // Count occurrences within the effective range
                                    $current = $effectiveStart->copy();
                                    while ($current->lte($effectiveEnd)) {
                                        if (strtolower($current->format('l')) === $dayName) {
                                            $totalOccurrences++;
                                        }
                                        $current->addDay();
                                    }
                                }
                                $totalOccurrences += count($availabilities->where('is_recurring', false));

                                $html .= '<p class="text-xs text-amber-600 dark:text-amber-400"><strong>Note:</strong> All ' . $totalOccurrences . ' availability occurrence(s) in this date range will be replaced by the replacement doctor.</p>';
                                $html .= '</div></div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(
                                fn($get) =>
                                $get('original_doctor_id') &&
                                    $get('start_date') &&
                                    $get('end_date') &&
                                    in_array($get('replacement_type'), ['all', 'permanent'])
                            )
                            ->columnSpanFull(),
                    ])
                    ->visible(
                        fn($get) =>
                        $get('original_doctor_id') &&
                            $get('start_date') &&
                            $get('end_date') &&
                            in_array($get('replacement_type'), ['all', 'permanent'])
                    )
                    ->columnSpanFull(),
                Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Select::make('reason')
                            ->label('Reason')
                            ->options([
                                'leave' => 'Doctor on Leave',
                                'unavailable' => 'Doctor Unavailable',
                                'emergency' => 'Emergency',
                                'sick' => 'Doctor Sick',
                                'other' => 'Other',
                            ])
                            ->default('leave')
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Additional notes about this replacement...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}