<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Core\Concerns\ResolvesPatientClientIdentity;
use Modules\Core\Contracts\ProvidesClientIdentity;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Classes\Services\PregnancyRiskService;
use Modules\MCH\Enums\EddSource;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;
use Modules\Patient\Models\Patient;

class PregnancyEpisode extends BaseModel implements ProvidesClientIdentity
{
    use HasFactory, HasUuids, ResolvesPatientClientIdentity, SoftDeletes;

    protected $keyType = 'string';

    protected $attributes = [
        'outcome' => PregnancyOutcome::ACTIVE->value,
        'risk_level' => RiskLevel::LOW->value,
        'edd_source' => EddSource::LMP->value,
        'multiple_gestation' => false,
        'risk_override' => false,
    ];

    protected $fillable = [
        'patient_id',
        'branch_id',
        'gravida',
        'parity',
        'lmp',
        'edd',
        'edd_source',
        'multiple_gestation',
        'risk_level',
        'risk_override',
        'risk_factors',
        'booking_date',
        'booking_ga_weeks',
        'outcome',
        'recorded_by',
    ];

    protected $casts = [
        'lmp' => 'date',
        'edd' => 'date',
        'edd_source' => EddSource::class,
        'multiple_gestation' => 'boolean',
        'risk_level' => RiskLevel::class,
        'risk_override' => 'boolean',
        'risk_factors' => 'array',
        'booking_date' => 'date',
        'outcome' => PregnancyOutcome::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $episode): void {
            if (! $episode->risk_override) {
                $episode->risk_level = app(PregnancyRiskService::class)->deriveRiskLevel(
                    array_map(fn ($factor) => $factor instanceof PregnancyRiskFactor ? $factor->value : $factor, $episode->risk_factors ?? []),
                );
            }

            if ($episode->booking_date !== null && $episode->lmp !== null) {
                $episode->booking_ga_weeks = (int) floor($episode->lmp->diffInDays($episode->booking_date) / 7);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Gestational age at an arbitrary date, derived from LMP (design: "weeks/days at any date").
     *
     * @return array{weeks: int, days: int}|null
     */
    public function gestationalAgeAt(Carbon $date): ?array
    {
        if ($this->lmp === null) {
            return null;
        }

        $days = (int) max(0, $this->lmp->diffInDays($date));

        return ['weeks' => intdiv($days, 7), 'days' => $days % 7];
    }
}
