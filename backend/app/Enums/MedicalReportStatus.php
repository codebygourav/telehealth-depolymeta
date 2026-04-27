<?php

namespace App\Enums;

enum MedicalReportStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
    case SHARED = 'shared';
    case UPLOADED = 'uploaded';
    case CONCLUSION_REPORT = 'conclusion_report';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE          => 'Active',
            self::ARCHIVED        => 'Archived',
            self::SHARED          => 'Shared',
            self::UPLOADED        => 'Uploaded',
            self::CONCLUSION_REPORT => 'Conclusion Report',
        };
    }

    public static function default(): string
    {
        return self::ACTIVE->value;
    }
}
