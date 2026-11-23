<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Concerns\ResolvesPatientClientIdentity;
use Modules\Core\Contracts\ProvidesClientIdentity;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\PostnatalContact;
use Modules\Patient\Models\Patient;

/**
 * A postnatal (PNC) check of the mother or the baby on a POSTNATAL encounter.
 *
 * @property string $id
 * @property string $encounter_id
 * @property string $patient_id
 * @property string $branch_id
 * @property string|null $delivery_record_id
 * @property string $subject
 * @property PostnatalContact $contact
 * @property int|null $days_postpartum
 * @property array<int, string>|null $danger_signs
 * @property bool $referral_required
 */
class PostnatalVisitAssessment extends BaseModel implements ProvidesClientIdentity
{
    use HasFactory, HasUuids, ResolvesPatientClientIdentity;

    protected $table = 'mch_postnatal_visit_assessments';

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'branch_id',
        'delivery_record_id',
        'subject',
        'contact',
        'days_postpartum',
        'temperature',
        'systolic_bp',
        'diastolic_bp',
        'uterus',
        'lochia',
        'breastfeeding',
        'cord',
        'danger_signs',
        'family_planning_counselled',
        'family_planning_method',
        'referral_required',
        'referral_destination',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'contact' => PostnatalContact::class,
        'days_postpartum' => 'integer',
        'temperature' => 'decimal:1',
        'danger_signs' => 'array',
        'family_planning_counselled' => 'boolean',
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

    public function deliveryRecord(): BelongsTo
    {
        return $this->belongsTo(DeliveryRecord::class);
    }
}
