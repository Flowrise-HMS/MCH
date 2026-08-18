<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Concerns\ResolvesPatientClientIdentity;
use Modules\Core\Contracts\ProvidesClientIdentity;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\Patient\Models\Patient;

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
    ];

    protected $casts = [
        'type' => GrowthMeasurementType::class,
        'value' => 'decimal:3',
        'date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
