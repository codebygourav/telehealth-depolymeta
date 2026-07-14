<?php

namespace App\Services;

use App\Models\DoctorAddedMedicine;
use App\Models\Medicine;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PrescriptionDraftParser
{
    public function parse(string $inputText, ?string $doctorId = null): array
    {
        $translatedText = $this->translateToEnglish($inputText);
        $sourceText = $this->normalizeText($translatedText);
        $text = $this->canonicalizeForParsing($sourceText);
        $warnings = [];
        $missingFields = [];

        [$medicine, $medicineWarnings] = $this->resolveMedicine($sourceText, $text, $doctorId);
        $warnings = [...$warnings, ...$medicineWarnings];

        $medicineName = $medicine['name'] ?? $this->extractMedicineName($sourceText, $text);
        $medicineType = $medicine['type'] ?? $this->extractMedicationType($text) ?? 'tablet';
        $strength = $this->extractStrength($text) ?? ($medicine['defaults']['strength'] ?? null);
        $dosage = $this->extractDosage($text) ?? ($medicine['defaults']['dosage'] ?? null);
        $frequency = $this->extractFrequency($text) ?? ($medicine['defaults']['frequency'] ?? null);
        $timings = $this->extractTimings($text);
        if (empty($timings) && ! blank($medicine['defaults']['timing'] ?? null)) {
            $timings[] = $medicine['defaults']['timing'];
        }
        $meal = $this->extractMeal($text) ?? ($medicine['defaults']['meal'] ?? null);
        [$startDate, $endDate, $durationLabel] = $this->extractDates($text);
        $durationLabel ??= $medicine['defaults']['duration'] ?? null;
        $route = $this->extractRoute($text) ?? ($medicine['defaults']['route'] ?? null);
        $applicationArea = $this->extractApplicationArea($text, $medicine['options']['application_areas'] ?? []);
        $instructions = $this->extractInstructions($sourceText, $text) ?: ($medicine['defaults']['instructions'] ?? '');
        $followUpNote = $this->extractFollowUpNote($sourceText, $text);

        $form = [
            'medicine_id' => ($medicine['source'] ?? null) === 'inventory' ? ($medicine['id'] ?? null) : null,
            'medicine_name' => $medicineName,
            'medicine_source' => $medicine['source'] ?? null,
            'medication_type' => $medicineType ?: 'tablet',
            'strength' => $strength,
            'dosage' => $dosage,
            'frequency' => $frequency,
            'timing_morning' => in_array('morning', $timings, true),
            'timing_afternoon' => in_array('afternoon', $timings, true),
            'timing_evening' => in_array('evening', $timings, true),
            'timing_night' => in_array('night', $timings, true),
            'meal' => $meal,
            'duration' => $durationLabel,
            'route' => $route,
            'application_area' => $applicationArea,
            'is_sos' => $frequency === 'SOS',
            'instructions' => $instructions,
            'follow_up_note' => $followUpNote,
            'medicine_options' => $medicine['options'] ?? [],
            'field_rules' => $medicine['field_rules'] ?? [],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'stamp_preference' => 'only_global',
        ];

        foreach (['medicine_name', 'dosage', 'frequency', 'meal'] as $field) {
            if (blank($form[$field])) {
                $missingFields[] = $field;
            }
        }

        if (! $medicine) {
            $warnings[] = 'Medicine was not matched with inventory. Review the medicine name before saving.';
        }

        if (empty($timings) && $frequency !== 'SOS') {
            $warnings[] = 'No explicit timing words were detected. Add morning, afternoon, evening, or night if needed.';
        }

        $confidenceScore = max(25, 100 - (count($warnings) * 15) - (count($missingFields) * 20));

        return [
            'form' => $form,
            'warnings' => array_values(array_unique($warnings)),
            'missing_fields' => array_values(array_unique($missingFields)),
            'confidence_score' => $confidenceScore,
        ];
    }

    private function normalizeText(string $inputText): string
    {
        return trim((string) Str::of($inputText)->replaceMatches('/\s+/', ' '));
    }

    private function canonicalizeForParsing(string $text): string
    {
        $text = $this->normalizeDigits($this->normalizeText($text));

        $replacements = [
            '/\b(auto|default)\b/iu' => ' auto ',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = (string) preg_replace($pattern, $replacement, $text);
        }

        return $this->normalizeText(Str::lower($text));
    }

    private function normalizeDigits(string $text): string
    {
        $text = strtr($text, [
            '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
            '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9',
            '੦' => '0', '੧' => '1', '੨' => '2', '੩' => '3', '੪' => '4',
            '੫' => '5', '੬' => '6', '੭' => '7', '੮' => '8', '੯' => '9',
        ]);

        return (string) preg_replace_callback(
            '/\b(one|two|three|four|five|six|seven|eight|nine|ten|fourteen|fifteen|twenty|thirty)\b/i',
            fn(array $matches): string => [
                'one' => '1',
                'two' => '2',
                'three' => '3',
                'four' => '4',
                'five' => '5',
                'six' => '6',
                'seven' => '7',
                'eight' => '8',
                'nine' => '9',
                'ten' => '10',
                'fourteen' => '14',
                'fifteen' => '15',
                'twenty' => '20',
                'thirty' => '30',
            ][strtolower($matches[1])] ?? $matches[1],
            $text
        );
    }

    private function resolveMedicine(string $sourceText, string $canonicalText, ?string $doctorId = null): array
    {
        $matchedInventory = Medicine::query()
            ->with('type:id,name')
            ->get()
            ->filter(function (Medicine $medicine) use ($sourceText, $canonicalText): bool {
                if ($medicine->getAttribute('speech_enabled') === false || $medicine->getAttribute('is_active') === false) {
                    return false;
                }

                $name = $this->normalizeText((string) $medicine->name);
                $canonicalName = $this->canonicalizeForParsing($name);
                $aliases = collect($medicine->spoken_aliases ?? [])
                    ->filter(fn($alias): bool => is_string($alias) && trim($alias) !== '')
                    ->values();

                $nameMatched = $name !== '' && (
                    $this->containsText($sourceText, $name)
                    || $this->containsText($canonicalText, $canonicalName)
                );

                $aliasMatched = $aliases->contains(function (string $alias) use ($sourceText, $canonicalText): bool {
                    return $this->containsText($sourceText, $alias)
                        || $this->containsText($canonicalText, $this->canonicalizeForParsing($alias));
                });

                return $nameMatched || $aliasMatched;
            })
            ->sortByDesc(fn (Medicine $medicine) => strlen((string) $medicine->name))
            ->values();

        if ($matchedInventory->count() > 1) {
            return [null, ['Multiple medicine candidates were detected. Dictate one medicine at a time in text mode.']];
        }

        if ($matchedInventory->count() === 1) {
            /** @var Medicine $medicine */
            $medicine = $matchedInventory->first();

            return [[
                'id' => $medicine->id,
                'name' => $medicine->name,
                'type' => $medicine->type?->name,
                'source' => 'inventory',
                'defaults' => [
                    'strength' => $medicine->default_strength,
                    'dosage' => $medicine->default_dosage,
                    'frequency' => $medicine->default_frequency,
                    'timing' => $medicine->default_timing,
                    'meal' => $medicine->default_meal,
                    'duration' => $medicine->default_duration,
                    'route' => $medicine->default_route,
                    'instructions' => $medicine->default_instructions,
                ],
                'options' => [
                    'strengths' => $this->arrayValues($medicine->strength_options),
                    'dosages' => $this->arrayValues($medicine->dosage_options),
                    'frequencies' => $this->arrayValues($medicine->frequency_options),
                    'timings' => $this->arrayValues($medicine->timing_options),
                    'meals' => $this->arrayValues($medicine->meal_options),
                    'routes' => $this->arrayValues($medicine->route_options),
                    'durations' => $this->arrayValues($medicine->duration_options),
                    'application_areas' => $this->arrayValues($medicine->application_area_options),
                ],
                'field_rules' => $this->arrayValues($medicine->field_rules),
            ], []];
        }

        if (! $doctorId) {
            return [null, []];
        }

        $matchedDoctorAdded = DoctorAddedMedicine::query()
            ->where('added_by_doctor', $doctorId)
            ->get(['id', 'name'])
            ->filter(function (DoctorAddedMedicine $medicine) use ($sourceText, $canonicalText): bool {
                $name = $this->normalizeText((string) $medicine->name);
                $canonicalName = $this->canonicalizeForParsing($name);

                return $name !== '' && (
                    $this->containsText($sourceText, $name)
                    || $this->containsText($canonicalText, $canonicalName)
                );
            })
            ->sortByDesc(fn (DoctorAddedMedicine $medicine) => strlen((string) $medicine->name))
            ->values();

        if ($matchedDoctorAdded->count() > 1) {
            return [null, ['Multiple custom medicine candidates were detected. Dictate one medicine at a time in text mode.']];
        }

        if ($matchedDoctorAdded->count() === 1) {
            /** @var DoctorAddedMedicine $medicine */
            $medicine = $matchedDoctorAdded->first();

            return [[
                'id' => $medicine->id,
                'name' => $medicine->name,
                'type' => null,
                'source' => 'doctor_added',
            ], []];
        }

        return [null, []];
    }

    private function containsText(string $haystack, string $needle): bool
    {
        $haystack = mb_strtolower($this->normalizeText($haystack));
        $needle = mb_strtolower($this->normalizeText($needle));

        return $needle !== '' && mb_stripos($haystack, $needle) !== false;
    }

    private function extractMedicineName(string $sourceText, string $canonicalText): ?string
    {
        $patterns = [
            '/(?:medicine|medication|drug)\s*(?:name)?\s*(?:is|:|-)?\s*([\p{L}\p{N}][\p{L}\p{N} \-+\/]{1,80}?)(?=(?: dosage| frequency| take | before | after | with | for \d| instructions?| notes?|$))/iu',
            '/(?:for|of)\s+([\p{L}\p{N}][\p{L}\p{N} \-+\/]{1,80}?)(?=\s+\d+(?:\.\d+)?\s*(?:mg|ml|tablet|tablets|capsule|capsules|drop|drops|puff|puffs))/iu',
            '/^\s*([\p{L}\p{N}][\p{L}\p{N} \-+\/]{1,80}?)(?=\s+\d+(?:\.\d+)?\s*(?:mg|ml|tablet|tablets|capsule|capsules|drop|drops|puff|puffs))/iu',
        ];

        foreach ([$sourceText, $canonicalText] as $text) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    return trim($matches[1], " \t\n\r\0\x0B,.-");
                }
            }
        }

        return null;
    }

    private function extractMedicationType(string $text): ?string
    {
        foreach (['tablet', 'capsule', 'syrup', 'drop', 'drops', 'injection', 'cream', 'ointment'] as $type) {
            if (stripos($text, $type) !== false) {
                return $type === 'drops' ? 'drop' : $type;
            }
        }

        return null;
    }

    private function extractDosage(string $text): ?string
    {
        $patterns = [
            '/\b(\d+(?:\.\d+)?)\s*(tablet|tablets|capsule|capsules|drop|drops|puff|puffs|ml|units?)\b/i',
            '/\b(half|one|two|three)\s+(tablet|tablets|capsule|capsules)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return strtolower(trim($matches[0]));
            }
        }

        return null;
    }

    private function extractStrength(string $text): ?string
    {
        if (preg_match('/\b(\d+(?:\.\d+)?)\s*(mg|gm|mcg)\s*\/\s*(\d+(?:\.\d+)?)\s*(ml)\b/i', $text, $matches)) {
            return strtolower(trim($matches[0]));
        }

        if (preg_match('/\b(\d+(?:\.\d+)?)\s*(mg|gm|mcg|iu|%)\b/i', $text, $matches)) {
            return strtolower(trim($matches[0]));
        }

        return null;
    }

    private function extractFrequency(string $text): ?string
    {
        $normalized = strtolower($text);

        return match (true) {
            str_contains($normalized, 'sos'),
            str_contains($normalized, 'as needed'),
            str_contains($normalized, 'when needed') => 'SOS',
            str_contains($normalized, 'three times'),
            str_contains($normalized, 'thrice'),
            str_contains($normalized, 'tds') => 'TDS',
            str_contains($normalized, 'twice'),
            str_contains($normalized, 'two times'),
            str_contains($normalized, 'bd') => 'BD',
            str_contains($normalized, 'once'),
            str_contains($normalized, 'one time'),
            str_contains($normalized, 'od') => 'OD',
            default => null,
        };
    }

    private function extractTimings(string $text): array
    {
        $timings = [];

        foreach (['morning', 'afternoon', 'evening', 'night'] as $timing) {
            if (stripos($text, $timing) !== false) {
                $timings[] = $timing;
            }
        }

        return $timings;
    }

    private function extractMeal(string $text): ?string
    {
        $normalized = strtolower($text);

        return match (true) {
            str_contains($normalized, 'before meal'),
            str_contains($normalized, 'before food') => 'before_meal',
            str_contains($normalized, 'after meal'),
            str_contains($normalized, 'after food') => 'after_meal',
            str_contains($normalized, 'with meal'),
            str_contains($normalized, 'with food') => 'with_meal',
            default => null,
        };
    }

    private function extractDates(string $text): array
    {
        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = null;
        $durationLabel = null;

        if (preg_match('/(?:for\s+)?(\d+)\s+(day|days|week|weeks|month|months)\b/i', $text, $matches)) {
            $value = (int) $matches[1];
            $unit = strtolower($matches[2]);
            $durationLabel = $value . ' ' . $unit;

            $end = Carbon::today();

            if (str_starts_with($unit, 'day')) {
                $end->addDays(max(0, $value - 1));
            } elseif (str_starts_with($unit, 'week')) {
                $end->addWeeks($value)->subDay();
            } elseif (str_starts_with($unit, 'month')) {
                $end->addMonths($value)->subDay();
            }

            $endDate = $end->format('Y-m-d');
        }

        return [$startDate, $endDate, $durationLabel];
    }

    private function extractRoute(string $text): ?string
    {
        $normalized = strtolower($text);

        return match (true) {
            str_contains($normalized, 'intravenous'),
            preg_match('/\biv\b/i', $text) === 1 => 'IV (Intravenous)',
            str_contains($normalized, 'intramuscular'),
            preg_match('/\bim\b/i', $text) === 1 => 'IM (Intramuscular)',
            str_contains($normalized, 'subcutaneous'),
            preg_match('/\bsc\b/i', $text) === 1 => 'SC (Subcutaneous)',
            str_contains($normalized, 'sublingual') => 'Sublingual',
            str_contains($normalized, 'eye') => 'Eye',
            str_contains($normalized, 'ear') => 'Ear',
            str_contains($normalized, 'nasal'),
            str_contains($normalized, 'nose') => 'Nasal',
            str_contains($normalized, 'topical'),
            str_contains($normalized, 'apply') => 'Topical',
            str_contains($normalized, 'inhale'),
            str_contains($normalized, 'puff') => 'Inhalation',
            str_contains($normalized, 'nebul') => 'Nebulization',
            str_contains($normalized, 'oral'),
            str_contains($normalized, 'by mouth') => 'Oral',
            default => null,
        };
    }

    private function extractApplicationArea(string $text, array $configuredAreas): ?string
    {
        foreach ($configuredAreas as $area) {
            if (is_string($area) && $this->containsText($text, $area)) {
                return $area;
            }
        }

        $normalized = strtolower($text);

        return match (true) {
            str_contains($normalized, 'both eyes') => 'Both eyes',
            str_contains($normalized, 'left eye') => 'Left eye',
            str_contains($normalized, 'right eye') => 'Right eye',
            str_contains($normalized, 'both ears') => 'Both ears',
            str_contains($normalized, 'left ear') => 'Left ear',
            str_contains($normalized, 'right ear') => 'Right ear',
            str_contains($normalized, 'affected area') => 'Affected area',
            default => null,
        };
    }

    private function extractInstructions(string $sourceText, string $canonicalText): string
    {
        $patterns = [
            '/(?:instructions?|notes?|advise|advice)\s*[:\-]\s*(.+)$/iu',
        ];

        foreach ([$sourceText, $canonicalText] as $text) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        return '';
    }

    private function extractFollowUpNote(string $sourceText, string $canonicalText): ?string
    {
        $patterns = [
            '/(?:follow[\s-]?up|patient note|doctor note|review note)\s*[:\-]?\s*(.+)$/iu',
        ];

        foreach ([$sourceText, $canonicalText] as $text) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        return null;
    }

    private function arrayValues(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->filter(fn($item): bool => is_string($item) && trim($item) !== '')
            ->values()
            ->all();
    }

    private function translateToEnglish(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        try {
            $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=en&dt=t&q=' . urlencode($text);
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data[0]) && is_array($data[0])) {
                    $translated = '';
                    foreach ($data[0] as $item) {
                        $translated .= $item[0] ?? '';
                    }
                    return trim($translated) ?: $text;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Translation failed in PrescriptionDraftParser: ' . $e->getMessage());
        }

        return $text;
    }
}
