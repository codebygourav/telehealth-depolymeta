<?php

namespace App\Enums;

enum DosagePreset: string
{
    // Tablets / Capsules
    case TABLET_0_5 = '0.5 tablet';
    case TABLET_1 = '1 tablet';
    case TABLET_1_5 = '1.5 tablets';
    case TABLET_2 = '2 tablets';
    case TABLET_3 = '3 tablets';

    // Liquids / Syrups
    case LIQUID_2_5 = '2.5 ml';
    case LIQUID_5 = '5 ml';
    case LIQUID_10 = '10 ml';
    case LIQUID_15 = '15 ml';
    case LIQUID_20 = '20 ml';

    // Injections
    case INJ_VIAL_1 = '1 vial';
    case INJ_AMP_1 = '1 ampoule';
    case INJ_0_5 = '0.5 ml';
    case INJ_1 = '1 ml';
    case INJ_2 = '2 ml';

    // Drops
    case DROPS_1 = '1 drop';
    case DROPS_2 = '2 drops';
    case DROPS_3 = '3 drops';
    case DROPS_4 = '4 drops';

    // Ointments / Creams
    case CREAM_THIN = 'thin layer';
    case CREAM_PEA = 'pea-sized amount';

    // Inhaler
    case PUFF_1 = '1 puff';
    case PUFF_2 = '2 puffs';
    case PUFF_3 = '3 puffs';

    // Powder
    case POWDER_SACHET = '1 sachet';
    case POWDER_SPOON = '1 spoon';

    // Common
    case AS_PRESCRIBED = 'as prescribed';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::TABLET_0_5 => '½ Tablet',
            self::TABLET_1 => '1 Tablet',
            self::TABLET_1_5 => '1½ Tablets',
            self::TABLET_2 => '2 Tablets',
            self::TABLET_3 => '3 Tablets',

            self::LIQUID_2_5 => '2.5 ml (½ spoon)',
            self::LIQUID_5 => '5 ml (1 spoon)',
            self::LIQUID_10 => '10 ml (2 spoons)',
            self::LIQUID_15 => '15 ml (3 spoons)',
            self::LIQUID_20 => '20 ml (4 spoons)',

            self::INJ_VIAL_1 => '1 Vial',
            self::INJ_AMP_1 => '1 Ampoule',
            self::INJ_0_5 => '0.5 ml',
            self::INJ_1 => '1 ml',
            self::INJ_2 => '2 ml',

            self::DROPS_1 => '1 Drop',
            self::DROPS_2 => '2 Drops',
            self::DROPS_3 => '3 Drops',
            self::DROPS_4 => '4 Drops',

            self::CREAM_THIN => 'Thin layer',
            self::CREAM_PEA => 'Pea-sized amount',

            self::PUFF_1 => '1 Puff',
            self::PUFF_2 => '2 Puffs',
            self::PUFF_3 => '3 Puffs',

            self::POWDER_SACHET => '1 Sachet',
            self::POWDER_SPOON => '1 Spoon',

            self::AS_PRESCRIBED => 'As prescribed',
            self::CUSTOM => 'Custom Dosage...',
        };
    }

    public static function forType(?string $type): array
    {
        $type = strtolower($type ?? '');

        $presets = [];

        if (str_contains($type, 'tablet') || str_contains($type, 'capsule')) {
            $presets = [
                self::TABLET_0_5,
                self::TABLET_1,
                self::TABLET_1_5,
                self::TABLET_2,
                self::TABLET_3,
            ];
        } elseif (str_contains($type, 'liquid') || str_contains($type, 'syrup') || str_contains($type, 'suspension') || str_contains($type, 'solution')) {
            $presets = [
                self::LIQUID_2_5,
                self::LIQUID_5,
                self::LIQUID_10,
                self::LIQUID_15,
                self::LIQUID_20,
            ];
        } elseif (str_contains($type, 'drop')) {
            $presets = [
                self::DROPS_1,
                self::DROPS_2,
                self::DROPS_3,
                self::DROPS_4,
            ];
        } elseif (str_contains($type, 'cream') || str_contains($type, 'ointment') || str_contains($type, 'gel')) {
            $presets = [
                self::CREAM_THIN,
                self::CREAM_PEA,
            ];
        } elseif (str_contains($type, 'injection') || str_contains($type, 'vial') || str_contains($type, 'ampoule')) {
            $presets = [
                self::INJ_VIAL_1,
                self::INJ_AMP_1,
                self::INJ_0_5,
                self::INJ_1,
                self::INJ_2,
            ];
        } elseif (str_contains($type, 'inhaler') || str_contains($type, 'puff')) {
            $presets = [
                self::PUFF_1,
                self::PUFF_2,
                self::PUFF_3,
            ];
        } elseif (str_contains($type, 'powder') || str_contains($type, 'sachet')) {
            $presets = [
                self::POWDER_SACHET,
                self::POWDER_SPOON,
            ];
        } else {
            // General fallback
            $presets = [
                self::TABLET_1,
                self::LIQUID_5,
                self::DROPS_2,
            ];
        }

        $presets[] = self::AS_PRESCRIBED;
        $presets[] = self::CUSTOM;

        $options = [];
        foreach ($presets as $preset) {
            $options[$preset->value] = $preset->label();
        }

        return $options;
    }
}
