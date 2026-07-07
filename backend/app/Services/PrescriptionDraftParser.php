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
        $dosage = $this->extractDosage($text);
        $frequency = $this->extractFrequency($text);
        $timings = $this->extractTimings($text);
        $meal = $this->extractMeal($text);
        [$startDate, $endDate] = $this->extractDates($text);
        $instructions = $this->extractInstructions($sourceText, $text);

        $form = [
            'medicine_id' => ($medicine['source'] ?? null) === 'inventory' ? ($medicine['id'] ?? null) : null,
            'medicine_name' => $medicineName,
            'medicine_source' => $medicine['source'] ?? null,
            'medication_type' => $medicineType ?: 'tablet',
            'dosage' => $dosage,
            'frequency' => $frequency,
            'timing_morning' => in_array('morning', $timings, true),
            'timing_afternoon' => in_array('afternoon', $timings, true),
            'timing_evening' => in_array('evening', $timings, true),
            'timing_night' => in_array('night', $timings, true),
            'meal' => $meal,
            'instructions' => $instructions,
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
        return strtr($text, [
            '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
            '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9',
            '੦' => '0', '੧' => '1', '੨' => '2', '੩' => '3', '੪' => '4',
            '੫' => '5', '੬' => '6', '੭' => '7', '੮' => '8', '੯' => '9',
        ]);
    }

    private function resolveMedicine(string $sourceText, string $canonicalText, ?string $doctorId = null): array
    {
        $matchedInventory = Medicine::query()
            ->with('type:id,name')
            ->get(['id', 'name', 'type_id'])
            ->filter(function (Medicine $medicine) use ($sourceText, $canonicalText): bool {
                $name = $this->normalizeText((string) $medicine->name);
                $canonicalName = $this->canonicalizeForParsing($name);

                return $name !== '' && (
                    $this->containsText($sourceText, $name)
                    || $this->containsText($canonicalText, $canonicalName)
                );
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
            '/\b(\d+(?:\.\d+)?)\s*(tablet|tablets|capsule|capsules|drop|drops|puff|puffs|ml|mg|gm|mcg|units?)\b/i',
            '/\b(half|one|two|three)\s+(tablet|tablets|capsule|capsules)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return strtolower(trim($matches[0]));
            }
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

        if (preg_match('/(?:for\s+)?(\d+)\s+(day|days|week|weeks|month|months)\b/i', $text, $matches)) {
            $value = (int) $matches[1];
            $unit = strtolower($matches[2]);

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

        return [$startDate, $endDate];
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
