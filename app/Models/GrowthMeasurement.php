<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Concerns\ResolvesPatientClientIdentity;
use Modules\Core\Contracts\ProvidesClientIdentity;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Classes\Services\GrowthZScoreService;
use Modules\MCH\Enums\GrowthIndicator;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\Patient\Models\Patient;

/**
 * @property string $id
 * @property string $patient_id
 * @property string|null $encounter_id
 * @property string $branch_id
 * @property GrowthMeasurementType $type
 * @property string $value
 * @property string $unit
 * @property \Illuminate\Support\Carbon|null $date
 * @property string|null $z_score
 * @property string|null $z_indicator
 * @property string|null $whz
 */
class GrowthMeasurement extends BaseModel implements ProvidesClientIdentity
{
    use HasFactory, HasUuids, ResolvesPatientClientIdentity;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_id',
        'encounter_id',
        'branch_id',
        'type',
        'value',
        'unit',
        'date',
        'recorded_by',
        'z_score',
        'z_indicator',
        'whz',
    ];

    protected $casts = [
        'type' => GrowthMeasurementType::class,
        'value' => 'decimal:3',
        'date' => 'date',
        'z_score' => 'decimal:2',
        'whz' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // WHO Z-scores follow the measurement whichever path writes it.
        static::saving(function (GrowthMeasurement $measurement): void {
            if ($measurement->isDirty(['type', 'value', 'unit', 'date', 'patient_id']) || ! $measurement->exists) {
                app(GrowthZScoreService::class)->apply($measurement);
            }
        });

        static::saved(function (GrowthMeasurement $measurement): void {
            if ($measurement->wasChanged(['type', 'value', 'unit', 'date', 'patient_id']) || $measurement->wasRecentlyCreated) {
                app(GrowthZScoreService::class)->syncWeightForLength($measurement);
            }
        });

        static::deleted(fn (GrowthMeasurement $measurement) => app(GrowthZScoreService::class)->syncWeightForLength($measurement));
    }

    public function indicator(): ?GrowthIndicator
    {
        return enum_try_from(GrowthIndicator::class, (string) $this->z_indicator);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
