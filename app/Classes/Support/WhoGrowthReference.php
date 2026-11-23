<?php

namespace Modules\MCH\Classes\Support;

use Modules\MCH\Enums\GrowthIndicator;

/**
 * Loads the WHO LMS tables shipped in database/data/who once per process.
 */
class WhoGrowthReference
{
    /** @var array<string, array<int, array<string, array{0: float, 1: float, 2: float}>>> */
    private static array $tables = [];

    /**
     * L, M, S for a sex (1 male, 2 female) and index (age in days, or
     * length/height in cm rounded to 0.1). Null when outside the table.
     *
     * @return array{0: float, 1: float, 2: float}|null
     */
    public static function lms(GrowthIndicator $indicator, int $sex, float $index): ?array
    {
        $table = self::table($indicator);
        $key = $indicator->byLength() ? number_format(round($index, 1), 1, '.', '') : (string) (int) $index;

        return $table[$sex][$key] ?? null;
    }

    /**
     * Ordered index → LMS rows for one sex (used to draw reference curves).
     *
     * @return array<string, array{0: float, 1: float, 2: float}>
     */
    public static function rows(GrowthIndicator $indicator, int $sex): array
    {
        return self::table($indicator)[$sex] ?? [];
    }

    /**
     * @return array<int, array<string, array{0: float, 1: float, 2: float}>>
     */
    private static function table(GrowthIndicator $indicator): array
    {
        if (isset(self::$tables[$indicator->value])) {
            return self::$tables[$indicator->value];
        }

        $path = module_path('MCH', 'database/data/who/'.$indicator->file());
        $rows = [1 => [], 2 => []];
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return self::$tables[$indicator->value] = $rows;
        }

        fgets($handle); // header

        while (($line = fgets($handle)) !== false) {
            $cols = explode("\t", trim($line));

            if (count($cols) < 5) {
                continue;
            }

            $sex = (int) $cols[0];
            $key = $indicator->byLength() ? number_format((float) $cols[1], 1, '.', '') : (string) (int) $cols[1];
            $rows[$sex][$key] = [(float) $cols[2], (float) $cols[3], (float) $cols[4]];
        }

        fclose($handle);

        return self::$tables[$indicator->value] = $rows;
    }
}
