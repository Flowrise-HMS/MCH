<?php

declare(strict_types=1);

namespace Modules\MCH\Filament\Support;

use Filament\Support\Contracts\HasLabel;
use UnitEnum;

class InfolistFormat
{
    /**
     * @param  array<int, string>|null  $values
     * @param  class-string<UnitEnum&HasLabel>  $enumClass
     */
    public static function enumLabels(?array $values, string $enumClass): string
    {
        if ($values === null || $values === []) {
            return '-';
        }

        return collect($values)
            ->map(function ($value) use ($enumClass): string {
                if ($value instanceof UnitEnum) {
                    return method_exists($value, 'getLabel') ? (string) $value->getLabel() : $value->name;
                }

                $case = $enumClass::tryFrom((string) $value);

                return $case !== null && method_exists($case, 'getLabel')
                    ? (string) $case->getLabel()
                    : (string) $value;
            })
            ->join(', ');
    }

    public static function yesNo(?bool $value): string
    {
        if ($value === null) {
            return '-';
        }

        return $value ? 'Yes' : 'No';
    }
}
