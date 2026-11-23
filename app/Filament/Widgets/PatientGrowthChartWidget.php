<?php

namespace Modules\MCH\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Modules\MCH\Classes\Services\GrowthZScoreService;
use Modules\MCH\Classes\Support\WhoGrowthReference;
use Modules\MCH\Enums\GrowthIndicator;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Models\Patient;

/**
 * The child's measurements plotted over the WHO reference curves
 * (−3, −2, 0, +2, +3 SD) for the selected age-based indicator.
 */
class PatientGrowthChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Growth chart (WHO Child Growth Standards)';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public ?string $filter = 'wfa';

    #[Reactive]
    public ?string $patientId = null;

    #[On('mch-patient-data-updated')]
    public function refreshFromWorkspace(): void {}

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            GrowthIndicator::WEIGHT_FOR_AGE->value => GrowthIndicator::WEIGHT_FOR_AGE->getLabel(),
            GrowthIndicator::LENGTH_FOR_AGE->value => GrowthIndicator::LENGTH_FOR_AGE->getLabel(),
            GrowthIndicator::HEAD_CIRCUMFERENCE_FOR_AGE->value => GrowthIndicator::HEAD_CIRCUMFERENCE_FOR_AGE->getLabel(),
        ];
    }

    protected function getData(): array
    {
        return static::chartData($this->patientId, GrowthIndicator::tryFrom((string) $this->filter) ?? GrowthIndicator::WEIGHT_FOR_AGE);
    }

    /**
     * Monthly reference points up to the child's current age (min 12 months)
     * plus the child's own values placed on the nearest month.
     *
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    public static function chartData(?string $patientId, GrowthIndicator $indicator): array
    {
        $patient = filled($patientId)
            ? Patient::query()->withoutGlobalScopes()->find($patientId, ['id', 'gender', 'date_of_birth'])
            : null;
        $sex = match ($patient?->gender) {
            Gender::MALE => 1,
            Gender::FEMALE => 2,
            default => null,
        };

        if ($patient === null || $sex === null || $patient->date_of_birth === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $type = match ($indicator) {
            GrowthIndicator::LENGTH_FOR_AGE => GrowthMeasurementType::LENGTH_HEIGHT,
            GrowthIndicator::HEAD_CIRCUMFERENCE_FOR_AGE => GrowthMeasurementType::HEAD_CIRCUMFERENCE,
            default => GrowthMeasurementType::WEIGHT,
        };
        $dob = Carbon::parse($patient->date_of_birth)->startOfDay();
        $service = app(GrowthZScoreService::class);

        $points = GrowthMeasurement::query()
            ->withoutGlobalScope('branch')
            ->where('patient_id', $patient->id)
            ->where('type', $type->value)
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function (GrowthMeasurement $m) use ($dob, $service, $type): array {
                $month = (int) round($dob->diffInDays(Carbon::parse($m->date)->startOfDay()) / 30.4375);
                $value = $service->normalisedValue($type, (float) $m->value, (string) $m->unit);

                return $month >= 0 && $month <= 60 && $value !== null ? [$month => round($value, 2)] : [];
            });

        $maxMonth = (int) min(60, max(12, ($points->keys()->max() ?? 0) + 3, (int) ceil($dob->diffInDays(now()) / 30.4375)));
        $months = range(0, $maxMonth);
        $curve = function (float $sd) use ($indicator, $sex, $months): array {
            return array_map(function (int $month) use ($indicator, $sex, $sd): ?float {
                $lms = WhoGrowthReference::lms($indicator, $sex, (int) round($month * 30.4375));

                if ($lms === null) {
                    return null;
                }

                [$l, $m, $s] = $lms;

                return round(abs($l) < 1e-9 ? $m * exp($s * $sd) : $m * pow(1 + $l * $s * $sd, 1 / $l), 2);
            }, $months);
        };

        $reference = fn (string $label, float $sd, string $color, bool $dashed = false): array => [
            'label' => $label,
            'data' => $curve($sd),
            'borderColor' => $color,
            'borderWidth' => 1,
            'borderDash' => $dashed ? [4, 4] : [],
            'pointRadius' => 0,
            'fill' => false,
        ];

        return [
            'labels' => array_map(fn (int $month): string => (string) $month, $months),
            'datasets' => [
                $reference('−3 SD', -3, '#ef4444', true),
                $reference('−2 SD', -2, '#f59e0b'),
                $reference('Median', 0, '#22c55e'),
                $reference('+2 SD', 2, '#f59e0b'),
                $reference('+3 SD', 3, '#ef4444', true),
                [
                    'label' => __('Child'),
                    'data' => array_map(fn (int $month) => $points->get($month), $months),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => '#2563eb',
                    'pointRadius' => 4,
                    'spanGaps' => true,
                    'fill' => false,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
            'scales' => ['x' => ['title' => ['display' => true, 'text' => __('Age (months)')]]],
        ];
    }
}
