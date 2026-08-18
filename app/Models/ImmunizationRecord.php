<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Concerns\ResolvesPatientClientIdentity;
use Modules\Core\Contracts\ProvidesClientIdentity;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\Patient\Models\Patient;

class ImmunizationRecord extends BaseModel implements ProvidesClientIdentity
{
    use HasFactory, HasUuids, ResolvesPatientClientIdentity;

    protected $keyType = 'string';

    protected $fillable = [
        'patient_id',
        'branch_id',
        'vaccine_id',
        'dose_sequence',
        'status',
        'administered_date',
        'batch_lot',
        'site',
        'route',
        'reason',
        'encounter_id',
        'medication_id',
        'recorded_by',
    ];

    protected $casts = [
        'administered_date' => 'date',
        'status' => ImmunizationStatus::class,
        'dose_sequence' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
