<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Enums\PostnatalContact;
use Modules\MCH\Models\DeliveryRecord;
use Modules\MCH\Models\PostnatalVisitAssessment;

/**
 * Records a postnatal check (mother or baby) on a POSTNATAL encounter. The
 * contact (within 24 h, day 3, days 7–14, week 6) is derived from the days
 * since delivery unless given.
 */
class PostnatalVisitAssessmentService
{
    /** @var list<string> */
    public const DANGER_SIGNS = [
        'heavy_bleeding', 'fever', 'severe_headache', 'convulsions', 'foul_lochia', 'breathing_difficulty',
        'poor_feeding', 'jaundice', 'cord_infection', 'hypothermia', 'lethargy',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Encounter $encounter, array $data): PostnatalVisitAssessment
    {
        if ($encounter->type !== EncounterType::POSTNATAL) {
            throw new InvalidArgumentException('Postnatal visit assessment requires a POSTNATAL encounter.');
        }

        $delivery = $this->resolveDelivery($encounter, $data['delivery_record_id'] ?? null);
        $visitDate = Carbon::parse($data['visit_date'] ?? now());
        $days = $data['days_postpartum'] ?? ($delivery !== null
            ? (int) $delivery->delivered_at->copy()->startOfDay()->diffInDays($visitDate->copy()->startOfDay())
            : null);
        $contact = $data['contact'] ?? ($days !== null ? PostnatalContact::forDaysPostpartum((int) $days) : PostnatalContact::OTHER);

        return PostnatalVisitAssessment::updateOrCreate(
            ['encounter_id' => $encounter->id, 'subject' => $data['subject'] ?? 'mother'],
            [
                'patient_id' => $encounter->patient_id,
                'branch_id' => $encounter->branch_id,
                'delivery_record_id' => $delivery?->id,
                'contact' => $contact,
                'days_postpartum' => $days,
                'temperature' => $data['temperature'] ?? null,
                'systolic_bp' => $data['systolic_bp'] ?? null,
                'diastolic_bp' => $data['diastolic_bp'] ?? null,
                'uterus' => $data['uterus'] ?? null,
                'lochia' => $data['lochia'] ?? null,
                'breastfeeding' => $data['breastfeeding'] ?? null,
                'cord' => $data['cord'] ?? null,
                'danger_signs' => array_values(array_intersect((array) ($data['danger_signs'] ?? []), self::DANGER_SIGNS)),
                'family_planning_counselled' => (bool) ($data['family_planning_counselled'] ?? false),
                'family_planning_method' => $data['family_planning_method'] ?? null,
                'referral_required' => (bool) ($data['referral_required'] ?? false),
                'referral_destination' => $data['referral_destination'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $data['recorded_by'] ?? null,
            ],
        );
    }

    /**
     * The mother's latest delivery, or (for a baby's encounter) the delivery
     * the baby was born in.
     */
    public function resolveDelivery(Encounter $encounter, ?string $deliveryId = null): ?DeliveryRecord
    {
        if ($deliveryId !== null) {
            return DeliveryRecord::query()->withoutGlobalScope('branch')->find($deliveryId);
        }

        return DeliveryRecord::query()
            ->withoutGlobalScope('branch')
            ->where(fn ($query) => $query
                ->where('patient_id', $encounter->patient_id)
                ->orWhereHas('newborns', fn ($newborns) => $newborns->where('child_patient_id', $encounter->patient_id)))
            ->latest('delivered_at')
            ->first();
    }
}
