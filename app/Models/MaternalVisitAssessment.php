<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\Edema;
use Modules\MCH\Enums\MaternalPresentation;
use Modules\MCH\Enums\UrineResult;
use Modules\Patient\Models\Patient;

class MaternalVisitAssessment extends BaseModel
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'branch_id',
        'pregnancy_episode_id',
        'visit_number',
        'ga_weeks',
        'ga_days',
        'fetal_heart_rate',
        'presentation',
        'edema',
        'urine_protein',
        'urine_glucose',
        'danger_signs',
        'drugs_given',
        'referral_required',
        'referral_destination',
        'return_date',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'presentation' => MaternalPresentation::class,
        'edema' => Edema::class,
        'urine_protein' => UrineResult::class,
        'urine_glucose' => UrineResult::class,
        'danger_signs' => 'array',
        'drugs_given' => 'array',
        'referral_required' => 'boolean',
        'return_date' => 'date',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pregnancyEpisode(): BelongsTo
    {
        return $this->belongsTo(PregnancyEpisode::class);
    }
}
