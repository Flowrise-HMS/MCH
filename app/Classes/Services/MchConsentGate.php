<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;

/**
 * MCH data-sharing consent (`mch_records.data_consented`, captured when the
 * client-held ANC/CWC book is issued or later in the workspace).
 *
 * Consent governs SECONDARY USE and SHARING — FHIR read/search/$export, the
 * REST API and the AI assistant — never care: clinicians see and record MCH
 * data regardless. A patient counts as consented when any of their MCH books
 * (pregnancy or child health record owner) has `data_consented = true`.
 * Users with the `mch_override_consent` permission bypass the gate.
 * Disable entirely with `mch.consent.enforce = false`.
 */
class MchConsentGate
{
    public const OVERRIDE_PERMISSION = 'mch_override_consent';

    public function enforced(): bool
    {
        return (bool) config('mch.consent.enforce', true);
    }

    public function hasConsent(?string $patientId): bool
    {
        if ($patientId === null) {
            return false;
        }

        return DB::query()->fromSub($this->consentedPatientIds(), 'consented')->where('patient_id', $patientId)->exists();
    }

    /**
     * Whether $patientId's MCH data may be shared with $user (or the current user).
     */
    public function allowsSharing(?string $patientId, ?Authenticatable $user = null): bool
    {
        if (! $this->enforced()) {
            return true;
        }

        $user ??= auth()->user();

        if ($user !== null && method_exists($user, 'can') && $user->can(self::OVERRIDE_PERMISSION)) {
            return true;
        }

        return $this->hasConsent($patientId);
    }

    /**
     * Restrict a query on an MCH model with a `patient_id` column to consented
     * patients (no-op when the gate is off or overridden).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeToConsented(Builder $query, ?Authenticatable $user = null, string $column = 'patient_id'): Builder
    {
        if (! $this->enforced()) {
            return $query;
        }

        $user ??= auth()->user();

        if ($user !== null && method_exists($user, 'can') && $user->can(self::OVERRIDE_PERMISSION)) {
            return $query;
        }

        return $query->whereIn($query->qualifyColumn($column), $this->consentedPatientIds());
    }

    /**
     * Sub-query of patient ids with consent on any active or historical MCH book.
     */
    public function consentedPatientIds(): QueryBuilder
    {
        $consentedOwners = fn (string $ownerClass): QueryBuilder => DB::table('mch_records')
            ->select('owner_id')
            ->where('owner_type', $ownerClass)
            // Consent given on a book that was later lost or replaced still stands.
            ->where('data_consented', true);

        return DB::table((new PregnancyEpisode)->getTable())
            ->select('patient_id')
            ->whereIn('id', $consentedOwners(PregnancyEpisode::class))
            ->union(
                DB::table((new ChildHealthRecord)->getTable())
                    ->select('patient_id')
                    ->whereIn('id', $consentedOwners(ChildHealthRecord::class))
            );
    }

    /**
     * Record consent on the patient's active MCH book(s).
     */
    public function recordConsent(MchRecord $book, ?int $userId): MchRecord
    {
        $book->update([
            'data_consented' => true,
            'consented_at' => now(),
            'consented_by' => $userId,
        ]);

        return $book;
    }
}
