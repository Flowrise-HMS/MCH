<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\GrowthZScoreService;
use Modules\MCH\Enums\GrowthIndicator;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Filament\Widgets\PatientGrowthChartWidget;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

/**
 * Expected values come from the WHO Child Growth Standards SD tables
 * (published to 0.1 kg/cm, hence the tolerances).
 */
class GrowthZScoreTest extends TestCase
{
    use DatabaseTransactions;

    private GrowthZScoreService $service;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->service = app(GrowthZScoreService::class);
        $this->branch = Branch::factory()->create();
    }

    private function child(Gender $gender, string $dob): Patient
    {
        return Patient::withoutEvents(fn () => Patient::factory()->create([
            'branch_id' => $this->branch->id,
            'gender' => $gender,
            'date_of_birth' => $dob,
        ]));
    }

    private function measure(Patient $patient, GrowthMeasurementType $type, float $value, string $unit, string $date): GrowthMeasurement
    {
        return GrowthMeasurement::create([
            'patient_id' => $patient->id,
            'branch_id' => $this->branch->id,
            'type' => $type,
            'value' => $value,
            'unit' => $unit,
            'date' => $date,
        ]);
    }

    public function test_weight_for_age_matches_who_reference_points_for_boys_at_birth(): void
    {
        // WHO expanded table, boys WFA day 0: median 3.346, −2 SD 2.459, +2 SD 4.419.
        $this->assertEqualsWithDelta(0.0, $this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 0, 3.3464), 0.01);
        $this->assertEqualsWithDelta(-2.0, $this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 0, 2.459), 0.01);
        $this->assertEqualsWithDelta(2.0, $this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 0, 4.419), 0.01);
    }

    public function test_restricted_method_applies_beyond_three_sd_for_weight(): void
    {
        // Beyond ±3 SD WHO extrapolates linearly using the 2..3 SD distance
        // (boys WFA day 0: +2 SD 4.419, +3 SD 5.031, −2 SD 2.459, −3 SD 2.080).
        $z = $this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 0, 6.0);
        $this->assertEqualsWithDelta(3 + (6.0 - 5.031) / (5.031 - 4.419), $z, 0.02);

        $low = $this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 0, 1.6);
        $this->assertEqualsWithDelta(-3 + (1.6 - 2.080) / (2.459 - 2.080), $low, 0.02);
    }

    public function test_length_and_head_circumference_for_age_for_girls(): void
    {
        // WHO girls at birth: length median 49.1 (−2 SD 45.4).
        $this->assertEqualsWithDelta(0.0, $this->service->zScore(GrowthIndicator::LENGTH_FOR_AGE, 2, 0, 49.1), 0.05);
        $this->assertEqualsWithDelta(-2.0, $this->service->zScore(GrowthIndicator::LENGTH_FOR_AGE, 2, 0, 45.4), 0.05);
        // WHO expanded table, girls HCFA day 0: +2 SD 36.247.
        $this->assertEqualsWithDelta(2.0, $this->service->zScore(GrowthIndicator::HEAD_CIRCUMFERENCE_FOR_AGE, 2, 0, 36.247), 0.01);
    }

    public function test_age_tables_cover_the_whole_five_year_range(): void
    {
        // WHO expanded tables, day 1856 (the last row): boys weight median 18.4968 kg,
        // girls height median 109.9352 cm.
        $this->assertEqualsWithDelta(0.0, $this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 1856, 18.4968), 0.001);
        $this->assertEqualsWithDelta(0.0, $this->service->zScore(GrowthIndicator::LENGTH_FOR_AGE, 2, 1856, 109.9352), 0.001);
        $this->assertNotNull($this->service->zScore(GrowthIndicator::HEAD_CIRCUMFERENCE_FOR_AGE, 1, 1840, 50));
        $this->assertNotNull($this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 2, 1827, 18));
    }

    public function test_out_of_range_inputs_return_null(): void
    {
        $this->assertNull($this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 1900, 15));
        $this->assertNull($this->service->zScore(GrowthIndicator::WEIGHT_FOR_LENGTH, 1, 30.0, 2));
        $this->assertNull($this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 3, 10, 4));
        $this->assertNull($this->service->zScore(GrowthIndicator::WEIGHT_FOR_AGE, 1, 10, 0));
    }

    public function test_saving_a_measurement_stores_its_z_score(): void
    {
        $boy = $this->child(Gender::MALE, '2026-01-01');

        $weight = $this->measure($boy, GrowthMeasurementType::WEIGHT, 3346.4, 'g', '2026-01-01');

        $this->assertSame('wfa', $weight->z_indicator);
        $this->assertEqualsWithDelta(0.0, (float) $weight->z_score, 0.01);
    }

    public function test_weight_gets_whz_once_a_same_day_length_is_recorded(): void
    {
        // Boys weight-for-length at 45.0 cm: median 2.441 kg.
        $boy = $this->child(Gender::MALE, '2026-01-01');
        $weight = $this->measure($boy, GrowthMeasurementType::WEIGHT, 2.441, 'kg', '2026-01-05');
        $this->assertNull($weight->fresh()->whz);

        $this->measure($boy, GrowthMeasurementType::LENGTH_HEIGHT, 45.0, 'cm', '2026-01-05');

        $this->assertEqualsWithDelta(0.0, (float) $weight->fresh()->whz, 0.01);
    }

    public function test_weight_for_height_is_used_from_two_years(): void
    {
        // Girls weight-for-height at 85.0 cm: compare against the wfh table, not wfl.
        $girl = $this->child(Gender::FEMALE, '2023-01-01');
        $this->measure($girl, GrowthMeasurementType::LENGTH_HEIGHT, 85.0, 'cm', '2025-06-01');
        $weight = $this->measure($girl, GrowthMeasurementType::WEIGHT, 11.5, 'kg', '2025-06-01');

        $expected = $this->service->zScore(GrowthIndicator::WEIGHT_FOR_HEIGHT, 2, 85.0, 11.5);
        $this->assertNotNull($expected);
        $this->assertEqualsWithDelta($expected, (float) $weight->fresh()->whz, 0.001);
    }

    public function test_no_z_scores_without_sex_or_for_adults_or_fundal_height(): void
    {
        $unknown = $this->child(Gender::MALE, '2026-01-01');
        $unknown->forceFill(['gender' => null])->saveQuietly();
        $this->assertNull($this->measure($unknown, GrowthMeasurementType::WEIGHT, 4, 'kg', '2026-02-01')->z_score);

        $adult = $this->child(Gender::FEMALE, '1990-01-01');
        $this->assertNull($this->measure($adult, GrowthMeasurementType::WEIGHT, 60, 'kg', '2026-02-01')->z_score);
        $this->assertNull($this->measure($adult, GrowthMeasurementType::FUNDAL_HEIGHT, 30, 'cm', '2026-02-01')->z_score);
    }

    public function test_classification_bands(): void
    {
        $this->assertSame('Severely wasted', GrowthZScoreService::classify(GrowthIndicator::WEIGHT_FOR_LENGTH, -3.2)['label']);
        $this->assertSame('Stunted', GrowthZScoreService::classify('lhfa', -2.5)['label']);
        $this->assertSame('Normal', GrowthZScoreService::classify('wfa', 0.4)['label']);
        $this->assertNull(GrowthZScoreService::classify(null, 1.0));
    }

    public function test_recompute_command_backfills_existing_rows(): void
    {
        $boy = $this->child(Gender::MALE, '2026-01-01');
        $weight = $this->measure($boy, GrowthMeasurementType::WEIGHT, 3.3464, 'kg', '2026-01-01');
        $weight->forceFill(['z_score' => null, 'z_indicator' => null])->saveQuietly();

        $this->artisan('mch:recompute-zscores', ['--patient' => $boy->id])->assertSuccessful();

        $this->assertSame('wfa', $weight->fresh()->z_indicator);
    }

    public function test_growth_chart_plots_reference_curves_and_the_child(): void
    {
        $boy = $this->child(Gender::MALE, now()->subMonths(6)->toDateString());
        $this->measure($boy, GrowthMeasurementType::WEIGHT, 7.9, 'kg', now()->toDateString());

        $data = PatientGrowthChartWidget::chartData($boy->id, GrowthIndicator::WEIGHT_FOR_AGE);

        $this->assertCount(6, $data['datasets']);
        $this->assertSame(7.9, $data['datasets'][5]['data'][6]);
        $this->assertEqualsWithDelta(3.35, $data['datasets'][2]['data'][0], 0.01);
        $this->assertSame([], PatientGrowthChartWidget::chartData(null, GrowthIndicator::WEIGHT_FOR_AGE)['datasets']);
    }
}
