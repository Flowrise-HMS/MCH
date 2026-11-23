<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Core\Concerns\ResolvesPatientClientIdentity;
use Modules\Core\Contracts\ProvidesClientIdentity;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Database\Factories\DeliveryRecordFactory;
use Modules\MCH\Enums\DeliveryMode;
use Modules\MCH\Enums\DeliveryPlace;
use Modules\Patient\Models\Patient;

/**
 * Labour outcome for a pregnancy episode (one per episode); the mother is `patient_id`.
 *
 * @property string $id
 * @property string $pregnancy_episode_id
 * @property string $patient_id
 * @property string $branch_id
 * @property string|null $encounter_id
 * @property Carbon $delivered_at
 * @property DeliveryPlace $place
 * @property DeliveryMode $mode
 * @property int $number_of_babies
 * @property string $maternal_outcome
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Newborn> $newborns
 * @property-read PregnancyEpisode|null $pregnancyEpisode
 */
class DeliveryRecord extends BaseModel implements ProvidesClientIdentity
{
    use HasFactory, HasUuids, ResolvesPatientClientIdentity;

    protected $table = 'mch_delivery_records';

    protected $fillable = [
        'pregnancy_episode_id',
        'patient_id',
        'branch_id',
        'encounter_id',
        'delivered_at',
        'place',
        'mode',
        'attendant_type',
        'attendant_staff_id',
        'gestation_weeks',
        'number_of_babies',
        'blood_loss_ml',
        'complications',
        'maternal_outcome',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'place' => DeliveryPlace::class,
        'mode' => DeliveryMode::class,
        'complications' => 'array',
        'number_of_babies' => 'integer',
        'gestation_weeks' => 'integer',
        'blood_loss_ml' => 'integer',
    ];

    protected static function newFactory(): DeliveryRecordFactory
    {
        return DeliveryRecordFactory::new();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pregnancyEpisode(): BelongsTo
    {
        return $this->belongsTo(PregnancyEpisode::class);
    }

    public function newborns(): HasMany
    {
        return $this->hasMany(Newborn::class)->orderBy('birth_order');
    }

    public function postnatalVisits(): HasMany
    {
        return $this->hasMany(PostnatalVisitAssessment::class);
    }
}
