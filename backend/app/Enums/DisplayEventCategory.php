<?php

namespace App\Enums;

enum DisplayEventCategory: string
{
    case ADVERTISEMENT = 'advertisement';
    case EVENT = 'event';
    case HEALTH_CAMP = 'health_camp';
    case INFO = 'info';
    case ANNOUNCEMENT = 'announcement';
    case NOTICE = 'notice';
    case HEALTH_AWARENESS = 'health_awareness';
    case EMERGENCY_ALERT = 'emergency_alert';
    case FESTIVAL_GREETING = 'festival_greeting';
    case VACCINATION_CAMPAIGN = 'vaccination_campaign';
    case BLOOD_DONATION_CAMP = 'blood_donation_camp';
    case DOCTOR_PROMOTION = 'doctor_promotion';
    case DEPARTMENT_PROMOTION = 'department_promotion';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::ADVERTISEMENT => 'Advertisement',
            self::EVENT => 'Hospital Event',
            self::HEALTH_CAMP => 'Health Camp',
            self::INFO => 'Info',
            self::ANNOUNCEMENT => 'Announcement',
            self::NOTICE => 'Notice',
            self::HEALTH_AWARENESS => 'Health Awareness',
            self::EMERGENCY_ALERT => 'Emergency Alert',
            self::FESTIVAL_GREETING => 'Festival Greeting',
            self::VACCINATION_CAMPAIGN => 'Vaccination Campaign',
            self::BLOOD_DONATION_CAMP => 'Blood Donation Camp',
            self::DOCTOR_PROMOTION => 'Doctor Promotion',
            self::DEPARTMENT_PROMOTION => 'Department Promotion',
            self::CUSTOM => 'Custom',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ADVERTISEMENT => 'primary',
            self::EVENT => 'success',
            self::HEALTH_CAMP => 'success',
            self::INFO => 'info',
            self::ANNOUNCEMENT => 'warning',
            self::NOTICE => 'gray',
            self::HEALTH_AWARENESS => 'info',
            self::EMERGENCY_ALERT => 'danger',
            self::FESTIVAL_GREETING => 'warning',
            self::VACCINATION_CAMPAIGN => 'success',
            self::BLOOD_DONATION_CAMP => 'danger',
            self::DOCTOR_PROMOTION => 'primary',
            self::DEPARTMENT_PROMOTION => 'primary',
            self::CUSTOM => 'gray',
        };
    }

    public function titleLabel(): string
    {
        return match ($this) {
            self::EVENT,
            self::HEALTH_CAMP,
            self::VACCINATION_CAMPAIGN,
            self::BLOOD_DONATION_CAMP => 'Event Name',
            default => 'Title',
        };
    }

    public function titlePlaceholder(): string
    {
        return match ($this) {
            self::EVENT => 'Example: Free Cardiology Camp This Friday',
            self::HEALTH_AWARENESS => 'Example: Diabetes Awareness Week',
            self::ANNOUNCEMENT => 'Example: OPD Counter Shifted to Block B',
            self::EMERGENCY_ALERT => 'Example: Emergency Exit Drill in Progress',
            default => 'Example: Pregnancy Awareness Campaign',
        };
    }

    public function descriptionLabel(): string
    {
        return match ($this) {
            self::ANNOUNCEMENT,
            self::NOTICE,
            self::EMERGENCY_ALERT => 'Message',
            self::EVENT,
            self::HEALTH_CAMP => 'Description',
            default => 'Content',
        };
    }

    public function orderLabel(): string
    {
        return $this === self::EMERGENCY_ALERT ? 'Priority Order' : 'Display Order';
    }

    public function startLabel(): string
    {
        return in_array($this, [
            self::EVENT,
            self::HEALTH_CAMP,
            self::VACCINATION_CAMPAIGN,
            self::BLOOD_DONATION_CAMP,
        ], true) ? 'Start Date / Time' : 'Starts At';
    }

    public function endLabel(): string
    {
        return in_array($this, [
            self::EVENT,
            self::HEALTH_CAMP,
            self::VACCINATION_CAMPAIGN,
            self::BLOOD_DONATION_CAMP,
        ], true) ? 'End Date / Time' : 'Ends At';
    }

    public function showsScheduleFields(): bool
    {
        return ! in_array($this, [
            self::ANNOUNCEMENT,
            self::NOTICE,
        ], true);
    }

    public function defaultMediaType(): DisplayMediaType
    {
        return match ($this) {
            self::ANNOUNCEMENT,
            self::NOTICE,
            self::EMERGENCY_ALERT => DisplayMediaType::NOTE,
            self::EVENT,
            self::HEALTH_CAMP,
            self::VACCINATION_CAMPAIGN,
            self::BLOOD_DONATION_CAMP => DisplayMediaType::IMAGE,
            default => DisplayMediaType::IMAGE,
        };
    }

    public function showsImageField(?DisplayMediaType $mediaType = null): bool
    {
        if ($mediaType === null || $mediaType === DisplayMediaType::IMAGE) {
            return true;
        }

        return in_array($this, [
            self::ADVERTISEMENT,
            self::HEALTH_AWARENESS,
            self::DOCTOR_PROMOTION,
            self::DEPARTMENT_PROMOTION,
        ], true);
    }

    public function showsLinkField(?DisplayMediaType $mediaType = null): bool
    {
        if ($mediaType?->isLinkBased()) {
            return true;
        }

        return in_array($this, [
            self::EVENT,
            self::HEALTH_CAMP,
            self::INFO,
            self::VACCINATION_CAMPAIGN,
            self::BLOOD_DONATION_CAMP,
        ], true);
    }

    public function linkLabel(?DisplayMediaType $mediaType = null): string
    {
        return match (true) {
            $mediaType === DisplayMediaType::YOUTUBE => 'YouTube URL',
            in_array($this, [
                self::EVENT,
                self::HEALTH_CAMP,
                self::VACCINATION_CAMPAIGN,
                self::BLOOD_DONATION_CAMP,
            ], true) => 'Registration / Event URL',
            default => 'Media URL / Link',
        };
    }

    public function linkPlaceholder(?DisplayMediaType $mediaType = null): string
    {
        return match (true) {
            $mediaType === DisplayMediaType::YOUTUBE => 'https://www.youtube.com/watch?v=...',
            $mediaType === DisplayMediaType::VIDEO => 'https://example.com/video.mp4',
            in_array($this, [
                self::EVENT,
                self::HEALTH_CAMP,
                self::VACCINATION_CAMPAIGN,
                self::BLOOD_DONATION_CAMP,
            ], true) => 'https://example.com/register',
            default => 'Paste an external URL',
        };
    }

    public function showsPlaybackOptions(?DisplayMediaType $mediaType = null): bool
    {
        if (in_array($this, [
            self::NOTICE,
            self::ANNOUNCEMENT,
            self::EMERGENCY_ALERT,
        ], true)) {
            return false;
        }

        return in_array($mediaType, [
            DisplayMediaType::VIDEO,
            DisplayMediaType::YOUTUBE,
            DisplayMediaType::LINK,
            DisplayMediaType::INSTAGRAM,
            DisplayMediaType::IMAGE,
            null,
        ], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category) => [$category->value => $category->label()])
            ->all();
    }
}
