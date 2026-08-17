<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\ChildHealthRecordStatus;
use Modules\Patient\Models\Patient;

class ChildHealthRecord extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    protected $attributes = [
        'status' => ChildHealthRecordStatus::ACTIVE->value,
    ];

    protected $fillable = [
        'patient_id',
        'branch_id',
        'pregnancy_episode_id',
        'delivery_record_id',
        'date_of_birth',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'status' => ChildHealthRecordStatus::class,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pregnancyEpisode(): BelongsTo
    {
        return $this->belongsTo(PregnancyEpisode::class);
    }
}
