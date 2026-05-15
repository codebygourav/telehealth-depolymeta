<?php

namespace App\Enums;

enum VaccinationDocumentType: string
{
    case CERTIFICATE = 'certificate';
    case PRESCRIPTION = 'prescription';
    case SCAN = 'scan';
    case CONSENT_FORM = 'consent_form';

    public function label(): string
    {
        return match ($this) {
            self::CERTIFICATE => 'Certificate',
            self::PRESCRIPTION => 'Prescription',
            self::SCAN => 'Scan',
            self::CONSENT_FORM => 'Consent Form',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
