<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Modules\Clinical\Enums\EncounterType;

/**
 * Shared create flow for ANC / CWC / PNC visits: today's open encounter of
 * the right type is reused or created, then the MCH service records the visit.
 */
abstract class MchVisitController extends MchApiController
{
    abstract protected function encounterType(): EncounterType;

    /**
     * @param  array<string, mixed>  $data
     */
    abstract protected function record(\Modules\Clinical\Models\Encounter $encounter, array $data): Model;

    protected function createRecord(array $data): Model
    {
        $patient = $this->consentedPatient($data['patient_id']);
        $encounter = $this->visitEncounter($patient, $this->encounterType());

        return $this->record($encounter, [...$data, 'recorded_by' => auth()->id()]);
    }
}
