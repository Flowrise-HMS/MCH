<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Concerns\ResolvesPatientClientIdentity;
use Modules\Core\Contracts\ProvidesClientIdentity;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\ChildHealthRecordStatus;
use Modules\Patient\Models\Patient;

class ChildHealthRecord extends BaseModel implements ProvidesClientIdentity
{
    use HasFactory, HasUuids, ResolvesPatientClientIdentity, SoftDeletes;

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

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            if (static::query()->withoutGlobalScope('branch')->where('patient_id', $record->patient_id)->exists()) {
                throw new \RuntimeException('A child health record already exists for this patient.');
            }
        });
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
