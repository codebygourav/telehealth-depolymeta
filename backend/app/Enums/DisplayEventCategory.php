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

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category) => [$category->value => $category->label()])
            ->all();
    }
}
