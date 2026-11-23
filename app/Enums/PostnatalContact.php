<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * WHO-recommended postnatal contacts.
 */
enum PostnatalContact: string implements HasColor, HasDescription, HasLabel
{
    case WITHIN_24_HOURS = 'day_1';
    case DAY_3 = 'day_3';
    case DAYS_7_14 = 'day_7_14';
    case WEEK_6 = 'week_6';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::WITHIN_24_HOURS => __('Within 24 hours'),
            self::DAY_3 => __('Day 3 (48–72 h)'),
            self::DAYS_7_14 => __('Days 7–14'),
            self::WEEK_6 => __('Week 6'),
            self::OTHER => __('Other'),
        };
    }

    public static function forDaysPostpartum(int $days): self
    {
        return match (true) {
            $days <= 1 => self::WITHIN_24_HOURS,
            $days <= 3 => self::DAY_3,
            $days >= 7 && $days <= 14 => self::DAYS_7_14,
            $days >= 35 && $days <= 49 => self::WEEK_6,
            default => self::OTHER,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::WITHIN_24_HOURS => __('First postnatal check, before discharge or within 24 hours.'),
            self::DAY_3 => __('Second contact, 48–72 hours after birth.'),
            self::DAYS_7_14 => __('Third contact, between days 7 and 14.'),
            self::WEEK_6 => __('Final contact, around six weeks after birth.'),
            self::OTHER => __('Unscheduled postnatal contact.'),
        };
    }

    public function getColor(): string
    {
        return 'primary';
    }
}
