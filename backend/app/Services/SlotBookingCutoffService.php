<?php

namespace App\Services;

use App\Models\DoctorAvailability;
use App\Models\DoctorAvailabilityOverride;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotBookingCutoffService
{
    /**
     * Default lead-time rules when none are configured on the slot or in settings.
     *
     * @return array<int, array{value: int, unit: string}>
     */
    public function defaultRules(): array
    {
        // 1. Check new single-field booking settings
        $val = setting('booking.booking_cutoff_value');
        $unit = setting('booking.booking_cutoff_unit');

        if ($val !== null && $unit !== null && (int)$val > 0) {
            return [['value' => (int) $val, 'unit' => $unit]];
        }

        // 2. Fall back to old JSON database settings
        $configured = setting('booking.booking_cutoff_rules') ?? setting('app.booking_cutoff_rules');

        if (is_string($configured)) {
            $configured = json_decode($configured, true);
        }

        if (is_array($configured) && $configured !== []) {
            return $this->normalizeRules($configured);
        }

        // 3. Fall back to config values
        $fromConfigValue = config('settings.booking.sections.rules.fields.booking_cutoff_value.default');
        $fromConfigUnit = config('settings.booking.sections.rules.fields.booking_cutoff_unit.default');

        if ($fromConfigValue !== null && $fromConfigUnit !== null) {
            return [['value' => (int) $fromConfigValue, 'unit' => $fromConfigUnit]];
        }

        $fromConfig = config('settings.booking.default_cutoff_rules');

        if (is_string($fromConfig)) {
            $fromConfig = json_decode($fromConfig, true);
        }

        if (is_array($fromConfig) && $fromConfig !== []) {
            return $this->normalizeRules($fromConfig);
        }

        return [
            ['value' => 4, 'unit' => 'hours'],
            ['value' => 1, 'unit' => 'hours'],
        ];
    }

    public function wordpressAvailabilityMonths(): int
    {
        $fromDb = setting('booking.wordpress_availability_months');
        if ($fromDb !== null) {
            return max(1, (int) $fromDb);
        }

        $fromConfig = config('settings.booking.sections.rules.fields.wordpress_availability_months.default')
            ?? config('settings.booking.wordpress_availability_months', 3);

        return max(1, (int) $fromConfig);
    }

    /**
     * Resolved rules for an expanded slot (override already merged onto the clone when applicable).
     *
     * @return array<int, array{value: int, unit: string}>
     */
    public function rulesForAvailability(DoctorAvailability $availability): array
    {
        if (isset($availability->booking_cutoff_rules_source) && $availability->booking_cutoff_rules_source === 'override') {
            return $this->normalizeRules(is_array($availability->booking_cutoff_rules) ? $availability->booking_cutoff_rules : []);
        }

        if (is_array($availability->booking_cutoff_rules) && $availability->booking_cutoff_rules !== []) {
            return $this->normalizeRules($availability->booking_cutoff_rules);
        }

        return $this->defaultRules();
    }

    /**
     * @return array<int, array{value: int, unit: string}>
     */
    public function resolveRulesForDate(DoctorAvailability $availability, ?DoctorAvailabilityOverride $override = null): array
    {
        if ($override && $override->booking_cutoff_rules !== null) {
            return $this->normalizeRules($override->booking_cutoff_rules);
        }

        if (is_array($availability->booking_cutoff_rules) && $availability->booking_cutoff_rules !== []) {
            return $this->normalizeRules($availability->booking_cutoff_rules);
        }

        return $this->defaultRules();
    }

    public function rulesSourceForDate(DoctorAvailability $availability, ?DoctorAvailabilityOverride $override = null): string
    {
        if ($override && $override->booking_cutoff_rules !== null) {
            return 'override';
        }

        if (is_array($availability->booking_cutoff_rules) && $availability->booking_cutoff_rules !== []) {
            return 'availability';
        }

        return 'app_default';
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, array{value: int, unit: string}>
     */
    public function normalizeRules(array $rules): array
    {
        return collect($rules)
            ->map(function ($rule) {
                if (! is_array($rule)) {
                    return null;
                }

                $value = (int) ($rule['value'] ?? $rule['amount'] ?? 0);
                $unit = strtolower((string) ($rule['unit'] ?? 'hours'));

                if ($value <= 0) {
                    return null;
                }

                if (! in_array($unit, ['minutes', 'hours', 'days'], true)) {
                    $unit = 'hours';
                }

                return ['value' => $value, 'unit' => $unit];
            })
            ->filter()
            ->unique(fn(array $rule) => $rule['value'] . '_' . $rule['unit'])
            ->values()
            ->all();
    }

    public function ruleToMinutes(array $rule): int
    {
        $value = (int) ($rule['value'] ?? 0);

        return match ($rule['unit'] ?? 'hours') {
            'minutes' => $value,
            'days' => $value * 24 * 60,
            default => $value * 60,
        };
    }

    public function ruleApiKey(array $rule): string
    {
        $value = (int) ($rule['value'] ?? 0);
        $unit = strtolower((string) ($rule['unit'] ?? 'hours'));
        $unitLabel = match ($unit) {
            'minutes' => $value === 1 ? 'minute' : 'minutes',
            'days' => $value === 1 ? 'day' : 'days',
            default => $value === 1 ? 'hour' : 'hours',
        };

        return 'blocked_before_on_' . $value . '_' . $unitLabel . '_before';
    }

    /**
     * @return array<string, bool>
     */
    public function blockedBeforeFlags(Carbon $slotStart, array $rules, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $flags = [];

        foreach ($this->normalizeRules($rules) as $rule) {
            $cutoffStart = $slotStart->copy()->subMinutes($this->ruleToMinutes($rule));
            $flags[$this->ruleApiKey($rule)] = $now->greaterThanOrEqualTo($cutoffStart) && $now->lessThan($slotStart);
        }

        return $flags;
    }


    /**
     * Nested configured cutoff map for WordPress API.
     * These values indicate that a rule exists and applies to the slot.
     *
     * @return array<string, bool>
     */
    public function configuredBeforeForApi(array $rules, ?Carbon $slotStartTime = null, ?Carbon $now = null): array
    {
        $result = [];
        $now ??= Carbon::now();

        foreach ($this->normalizeRules($rules) as $rule) {
            $key = $this->ruleToSimpleKey($rule);
            if (! $slotStartTime) {
                $result[$key] = false;
                continue;
            }
            $cutoffStart = $slotStartTime->copy()->subMinutes($this->ruleToMinutes($rule));
            $result[$key] = $now->greaterThanOrEqualTo($cutoffStart);
        }

        return $result;
    }

    public function ruleToSimpleKey(array $rule): string
    {
        $value = (int) ($rule['value'] ?? 0);
        $unit = strtolower((string) ($rule['unit'] ?? 'hours'));
        $unitLabel = match ($unit) {
            'minutes' => 'minutes',
            'days' => $value === 1 ? 'day' : 'days',
            default => $value === 1 ? 'hour' : 'hours',
        };

        return $value . '_' . $unitLabel;
    }

    public function isWithinAnyCutoff(Carbon $slotStart, array $rules, ?Carbon $now = null): bool
    {
        $flags = $this->blockedBeforeFlags($slotStart, $rules, $now);

        return collect($flags)->contains(true);
    }

    /**
     * @return Collection<int, array{value: int, unit: string, label: string}>
     */
    public function presetOptions(): Collection
    {
        return collect([
            ['value' => 15, 'unit' => 'minutes', 'label' => '15 minutes'],
            ['value' => 30, 'unit' => 'minutes', 'label' => '30 minutes'],
            ['value' => 1, 'unit' => 'hours', 'label' => '1 hour'],
            ['value' => 2, 'unit' => 'hours', 'label' => '2 hours'],
            ['value' => 4, 'unit' => 'hours', 'label' => '4 hours'],
            ['value' => 24, 'unit' => 'hours', 'label' => '24 hours'],
            ['value' => 1, 'unit' => 'days', 'label' => '1 day'],
        ]);
    }
}
