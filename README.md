# MCH

Maternal and child health: pregnancy registry, ANC/PNC and child-welfare visits, growth monitoring, immunizations, delivery, and client-held MCH records.

Hard dependencies: Core, Patient, Clinical. Appointment is an optional peer (ANC return dates schedule through `OptionalClass` when the module is enabled).

## MCH-1 boundary

Notes — what MCH-1 intentionally does **not** do, so MCH-2/3/4 do not re-build it:

- `GrowthZScore` (MCH-3) must read **only** `GrowthMeasurement` rows of child types — `fundal_height` rows are excluded from WHO LMS math.
- `patient_relationships` is empty until MCH-4 newborn auto-registration fills it.
- `ChildHealthRecord.delivery_record_id` has no FK until MCH-4.
- MCH books/assessments are **read-only data stores** — no Filament CRUD was built in MCH-1 beyond what the phases above shipped; the ANC/CWC capture UIs ship with the visit workflows (MCH-2+) unless a follow-up task is added.
- Consent (`data_consented`) is stored, never enforced — do not claim GHILMIS/school-entry compliance until a flow checks it.

### Task 6 generated-column fallback

MariaDB 11.8 rejected the `active_owner_key` stored generated column (error 1901 on CHAR refs). One-active-book uniqueness is enforced in `MchBookIssuanceService` via `DB::transaction` + `lockForUpdate` on the owner row.
