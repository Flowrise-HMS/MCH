<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class ImmunizationScheduleItem extends BaseModel
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'immunization_schedule_id',
        'vaccine_id',
        'dose_sequence',
        'minimum_age_days',
        'maximum_age_days',
        'label',
    ];

    protected $casts = [
        'dose_sequence' => 'integer',
        'minimum_age_days' => 'integer',
        'maximum_age_days' => 'integer',
    ];

    protected static function bootBelongsToBranch(): void
    {
        // Schedule items are facility-wide catalogue rows — no branch_id.
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ImmunizationSchedule::class, 'immunization_schedule_id');
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }
}
