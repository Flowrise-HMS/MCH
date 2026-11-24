<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Http\Controllers\Api\ResourceApiController;
use Modules\MCH\Classes\Services\MchConsentGate;
use Modules\MCH\Classes\Services\MchWorkspaceService;
use Modules\Patient\Models\Patient;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * MCH data over the API is limited to clients with data-sharing consent
 * (`MchConsentGate`), unless the caller holds `mch_override_consent`:
 * other records are invisible (404) and cannot be written (403).
 */
abstract class MchApiController extends ResourceApiController
{
    /** Column holding the client's patient id. */
    protected string $patientColumn = 'patient_id';

    protected function scopedQuery(): Builder
    {
        return app(MchConsentGate::class)->scopeToConsented(parent::scopedQuery(), null, $this->patientColumn);
    }

    protected function consentedPatient(string $patientId): Patient
    {
        $patient = Patient::query()->findOrFail($patientId);

        if (! app(MchConsentGate::class)->allowsSharing($patient->id)) {
            throw new AccessDeniedHttpException('This client has not consented to sharing MCH data.');
        }

        return $patient;
    }

    protected function visitEncounter(Patient $patient, EncounterType $type): Encounter
    {
        return app(MchWorkspaceService::class)->ensureEncounter($patient, $type);
    }
}
