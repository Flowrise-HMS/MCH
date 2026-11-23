<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\MCH\Enums\BirthOutcome;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Models\Patient;

/**
 * One baby of a delivery; live births are registered as patients (`child_patient_id`).
 *
 * @property string $id
 * @property string $delivery_record_id
 * @property int $birth_order
 * @property Gender $sex
 * @property BirthOutcome $outcome
 * @property int|null $birth_weight_g
 * @property string|null $length_cm
 * @property string|null $head_circumference_cm
 * @property int|null $apgar_1
 * @property int|null $apgar_5
 * @property bool $resuscitated
 * @property string|null $child_patient_id
 * @property-read Patient|null $child
 */
class Newborn extends Model
{
    use HasUuids;

    protected $table = 'mch_newborns';

    protected $fillable = [
        'delivery_record_id',
        'birth_order',
        'sex',
        'outcome',
        'birth_weight_g',
        'length_cm',
        'head_circumference_cm',
        'apgar_1',
        'apgar_5',
        'resuscitated',
        'child_patient_id',
    ];

    protected $casts = [
        'sex' => Gender::class,
        'outcome' => BirthOutcome::class,
        'birth_order' => 'integer',
        'birth_weight_g' => 'integer',
        'length_cm' => 'decimal:1',
        'head_circumference_cm' => 'decimal:1',
        'apgar_1' => 'integer',
        'apgar_5' => 'integer',
        'resuscitated' => 'boolean',
    ];

    public function deliveryRecord(): BelongsTo
    {
        return $this->belongsTo(DeliveryRecord::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'child_patient_id');
    }
}
