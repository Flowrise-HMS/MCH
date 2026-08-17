<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\DevelopmentalScreen;
use Modules\MCH\Enums\FeedingMethod;
use Modules\Patient\Models\Patient;

class ChildVisitAssessment extends BaseModel
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'branch_id',
        'child_health_record_id',
        'feeding',
        'vitamin_a_given',
        'dewormed',
        'developmental_screen',
        'referral_required',
        'referral_destination',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'feeding' => FeedingMethod::class,
        'vitamin_a_given' => 'boolean',
        'dewormed' => 'boolean',
        'developmental_screen' => DevelopmentalScreen::class,
        'referral_required' => 'boolean',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function childHealthRecord(): BelongsTo
    {
        return $this->belongsTo(ChildHealthRecord::class);
    }
}
