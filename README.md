# MCH Module

> Register pregnancies and child-welfare clients, capture ANC/CWC visit assessments, store anthropometry, and issue client-held MCH books for FlowRise HMS.

The `MCH` module depends on `Modules\Core`, `Modules\Patient`, and `Modules\Clinical`. Appointment is an optional peer: ANC return dates schedule through `AncReturnScheduler` only when the Appointment module is enabled.

This document is the canonical guide for the module. It is meant to be useful to:

- midwives and clinicians who need to understand pregnancy registry, CWC, and book issuance
- administrators who need to assign Shield permissions for the MCH cluster
- developers who need to extend MCH-1 without rebuilding work reserved for later phases

## Current Status

MCH-1 (registry, visits, anthropometry, books) is **complete** for operational use.

What is available:

- pregnancy registry (`PregnancyEpisode`) with LMP/EDD, gravida/parity, risk factors, and derived or overridden `RiskLevel`
- CWC registry (`ChildHealthRecord`) linked optionally to a pregnancy episode
- ANC visit assessments on `EncounterType::ANTENATAL` encounters, including danger signs and optional fundal-height measurement
- CWC visit assessments on `EncounterType::CHILD_WELFARE` encounters, including feeding, vitamin A, deworming, and child anthropometry
- a shared `growth_measurements` store (child types plus obstetric `fundal_height`)
- client-held MCH book issuance and replacement with per-branch serials (`ANC-0001`, `CWC-0001`, …)
- FHIR `EpisodeOfCare` mapping for pregnancy episodes
- a Filament **MCH** cluster under NavigationGroup `CLINICAL` with six resources
- Shield policies for every resource model

Explicitly out of scope (deferred to later phases, not missing MCH-1 work):

- MCH-2: ANC visit workflow polish, PNC, immunization schedule UI
- MCH-3: WHO LMS Z-scores (`GrowthZScore` must read **only** child anthropometry rows — never `fundal_height`)
- MCH-4: delivery records, newborn auto-registration into `patient_relationships`, `ChildHealthRecord.delivery_record_id` FK
- consent enforcement (`data_consented` is stored, never checked) — do not claim GHILMIS/school-entry compliance until a flow reads it

## Mental Model

### The shortest explanation

MCH turns a **patient** into either a **pregnancy episode** (mother) or a **child health record** (child), then records **visit assessments** against Clinical encounters and optionally issues a **physical MCH book**.

```text
Patient
   │
   ├── PregnancyEpisode  ──► MaternalVisitAssessment (ANC encounter)
   │         │                        │
   │         │                        └── GrowthMeasurement (fundal_height)
   │         └── MchRecord (ANC book)
   │
   └── ChildHealthRecord ──► ChildVisitAssessment (CWC encounter)
             │                        │
             │                        └── GrowthMeasurement (weight, length, MUAC, HC)
             └── MchRecord (CWC book)
```

### Why this matters

- **Clinical owns the visit.** ANC/CWC assessments attach to existing `Encounter` rows of the matching type. MCH does not invent a second visit table.
- **Services own writes that have rules.** Visit capture, book issuance, risk derivation, and optional return scheduling go through `Classes\Services\*`. Filament create pages call those services instead of `Model::create()` for those flows.
- **Appointment is optional.** `AncReturnScheduler` must not hard-import Appointment classes; Core’s modular-ownership test guards that file.

## Data Model

All models extend `Modules\Core\Models\BaseModel` (auditing + `BelongsToBranch`) and use `HasUuids`. `PregnancyEpisode` and `ChildHealthRecord` also use `SoftDeletes`.

### Tables

| Table | Purpose | Soft deletes |
|-------|---------|--------------|
| `pregnancy_episodes` | One pregnancy per mother at a facility (LMP, EDD, risk, outcome) | yes |
| `child_health_records` | CWC registry row per child; optional link to pregnancy | yes |
| `growth_measurements` | Typed anthropometry / fundal height | no |
| `maternal_visit_assessments` | ANC visit findings on an antenatal encounter | no |
| `child_visit_assessments` | CWC visit findings on a child-welfare encounter | no |
| `mch_records` | Client-held book serials (morph owner = episode or CWC record) | no |

### Enums

Every enum implements Filament `HasLabel`, `HasColor`, and `HasDescription` (plus `values()`), matching Patient/Clinical.

| Enum | Role |
|------|------|
| `PregnancyRiskFactor` | Booking risk checklist; all cases except `OTHER` elevate risk |
| `RiskLevel` | `low` / `high` (derived unless `risk_override`) |
| `PregnancyOutcome` | Episode status including delivered, referred, stillbirth, maternal death |
| `EddSource` | How EDD was obtained (LMP, ultrasound, …) |
| `GrowthMeasurementType` | `weight`, `length_height`, `muac`, `head_circumference`, `fundal_height` — `isChildAnthropometry()` excludes fundal height |
| `MchRecordStatus` | `active`, `lost`, `damaged`, `replaced` |
| `ChildHealthRecordStatus` | CWC registry status |
| `DangerSign` | ANC danger signs |
| `MaternalPresentation` | Fetal presentation |
| `Edema` | Edema grade |
| `UrineResult` | Protein / glucose dipstick |
| `FeedingMethod` | CWC feeding |
| `DevelopmentalScreen` | CWC developmental screen result |

### One-active-book uniqueness (MariaDB 1901)

MariaDB 11.8 rejected a stored generated column on `CHAR` (`active_owner_key`, error 1901). One-active-book uniqueness is therefore **application-level**:

`MchBookIssuanceService` uses `DB::transaction` + `lockForUpdate` on the owner row **without global scopes**, then allocates the next serial under a branch-row lock (`MAX` under lock).

Do not re-introduce a CHAR-based generated unique column on this MariaDB version.

## Services

All service classes live in `Modules/MCH/app/Classes/Services` (`Modules\MCH\Classes\Services`), **not** `app/Services`.

| Class | Role |
|-------|------|
| `PregnancyRiskService` | Derives `RiskLevel` from risk-factor values |
| `MaternalVisitAssessmentService` | Records an ANC assessment; derives GA and visit number from the linked episode when omitted; writes fundal height into `growth_measurements` and BP/weight through Clinical `VitalSignService`; danger signs always set `referral_required` |
| `ChildVisitAssessmentService` | Records a CWC assessment plus child anthropometry rows |
| `MchBookIssuanceService` | Issues / replaces books; serial + one-active-book invariants |
| `AncReturnScheduler` | Optionally creates an Appointment return date when that module is enabled |

`PregnancyEpisode` still derives risk and booking GA in `saving` via `PregnancyRiskService` so Filament create/edit stay consistent without a dedicated pregnancy-create service. When `edd` is blank and `edd_source` is LMP, `saving` also derives EDD as LMP + 280 days.

`EpiDueService::generateDueRecords()` anchors child doses on the date of birth and maternal TT doses on the previous administered dose (dose 1 on the ANC booking date passed as `$anchorDate`), so a maternal dose is only generated once the one before it has been given. The workspace "Record pregnancy outcome" action closes the active episode by setting `PregnancyEpisode.outcome`; delivery details remain MCH-4.

`MchServiceProvider` resolves `pregnancyEpisodes`, `activePregnancyEpisode`, `childHealthRecord`, `immunizationRecords`, and `growthMeasurements` on `Patient` dynamically (the Clinical pattern) and registers three read-only relation managers on `PatientResource` through Core's `RelationManagersRegistry`.

The MCH Workspace registers patients through `Modules\Patient\Classes\Services\PatientService::create()` and its own Filament schemas (`registerForm`, `ancVisitForm`, `cwcVisitForm`), which reuse `quickElements()` from the resource form classes. A child registered with a mother gets a `patient_relationships` row (`type = mother`, subject = child, object = mother).

## Filament UI

Cluster: `Filament\Clusters\MCH\MchCluster` (slug `mch`, NavigationGroup `CLINICAL`).

Plugin: `Filament\MCHPlugin` (`Coolsam\Modules\ModuleFilamentPlugin`).

| Resource | Nav label | Slug | Create path |
|----------|-----------|------|-------------|
| `PregnancyEpisodeResource` | Pregnancy registry | `pregnancy-episodes` | model + risk observer |
| `ChildHealthRecordResource` | CWC registry | `child-health-records` | model |
| `MaternalVisitAssessmentResource` | ANC visits | `anc-visits` | `MaternalVisitAssessmentService::record()` |
| `ChildVisitAssessmentResource` | CWC visits | `cwc-visits` | `ChildVisitAssessmentService::record()` |
| `GrowthMeasurementResource` | Growth measurements | `growth-measurements` | model |
| `MchRecordResource` | MCH books | `mch-books` | `MchBookIssuanceService::issue()`; table action **Replace book** → `replace()` |

Resources follow the Clinical nest: `Filament/Clusters/MCH/Resources/{Name}/` with `Schemas/`, `Tables/`, and `Pages/`.

### Permissions

Each model has a Shield-style policy registered from `MchServiceProvider` (`ViewAny PregnancyEpisode`, `Create MaternalVisitAssessment`, …). Generate and assign those permissions in Shield before non-super-admin roles can open the cluster.

`data_consented` is a stored flag only; the workspace book action asks for it with a checkbox but no flow enforces it. The printable `VaccinationCard` page requires `View VaccinationCard` and lists every active child-schedule dose with its due/overdue classification.

## Administrator Guide

```text
1. Enable the MCH module (`modules_statuses.json` → MCH: true)
2. Run Core, Patient, Clinical, then MCH migrations
3. php artisan shield:generate  (or assign ViewAny/View/Create/Update/Delete for the six models)
4. Open Clinical → MCH cluster
5. Register a pregnancy or CWC record, then capture visits against matching encounters
6. Issue an MCH book from MCH books (serials are per branch)
```

Useful commands:

```bash
php artisan module:migrate MCH
vendor/bin/pest --configuration=phpunit-isolated.xml --compact Modules/MCH/tests
```

## Developer Guide

### Module boundaries

- Hard requires: Core, Patient, Clinical (`module.json`).
- Soft peer: Appointment (`AncReturnScheduler` + Core `OptionalClass` / ownership test).
- `patient_relationships` lives in Patient; MCH-1 does not fill it.
- `ChildHealthRecord.delivery_record_id` has no FK until MCH-4.

### Key classes

| Class | Role |
|-------|------|
| `Classes\Services\*` | Domain writes (see Services) |
| `Classes\Fhir\FhirEpisodeOfCareTransformer` | FHIR EpisodeOfCare for pregnancy episodes |
| `Filament\MCHPlugin` | Module Filament plugin |
| `Filament\Clusters\MCH\MchCluster` | Clinical sidebar cluster |
| `Policies\*` | Shield model policies |

### Providers

- `Providers\MchServiceProvider` — policies, service singletons, nested Event/Route providers
- `Providers\EventServiceProvider` — module events
- `Providers\RouteServiceProvider` — `routes/web.php` and `routes/api.php`

### Tests

`Modules/MCH/tests/Feature` covers registry, visits, books, FHIR transform, ANC scheduling, and module conventions (enum contracts, service path, clustered resources, policies).

Host `php artisan test --compact` can duplicate `--configuration` against this repo’s isolated PHPUnit file; use:

```bash
vendor/bin/pest --configuration=phpunit-isolated.xml --compact Modules/MCH/tests
```

### Metadata

- `module.json` — name `MCH`, alias `mch`, requires Core + Patient + Clinical
- `composer.json` — `flowrise-hms/mch`
- `config/config.php` — module name

## Related Documentation

- `docs/superpowers/plans/2026-08-15-mch-1-registry-and-visits.md`
- `docs/AGENT_KNOWLEDGE_BASE.md`
- `docs/shared/module-status.md`

## Summary

1. Services live in `app/Classes/Services`, never `app/Services`.
2. Enums implement Filament `HasLabel`, `HasColor`, and `HasDescription`.
3. Visit and book writes go through services; the Filament cluster is the operator UI.
4. One-active-book uniqueness is a transaction + row lock, not a generated column.
5. MCH-2/3/4 must not rebuild registry, assessments, anthropometry storage, or book issuance.
