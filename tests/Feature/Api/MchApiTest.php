<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Classes\Services\MchConsentGate;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\ApiResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

$modules = ['Core', 'Patient', 'Staff', 'Clinical', 'MCH'];

/**
 * A mother in $branch with a pregnancy and a consented ANC book.
 */
function mchApiConsentedEpisode(Branch $branch, bool $consented = true): PregnancyEpisode
{
    $mother = Patient::withoutEvents(fn () => Patient::factory()->female()->create(['branch_id' => $branch->id]));
    $episode = PregnancyEpisode::factory()->create(['patient_id' => $mother->id, 'branch_id' => $branch->id]);
    app(MchBookIssuanceService::class)->issue($episode, $branch, 'ANC', ['data_consented' => $consented]);

    return $episode;
}

function mchApiConsentedChild(Branch $branch): ChildHealthRecord
{
    $child = Patient::withoutEvents(fn () => Patient::factory()->child()->create(['branch_id' => $branch->id]));
    $record = ChildHealthRecord::factory()->create(['patient_id' => $child->id, 'branch_id' => $branch->id]);
    app(MchBookIssuanceService::class)->issue($record, $branch, 'CWC', ['data_consented' => true]);

    return $record;
}

beforeEach(fn () => config(['mch.consent.enforce' => true]));

describe('pregnancies', function () use ($modules): void {
    ApiResourceTestSuite::register([
        'uri' => 'pregnancies',
        'subject' => 'PregnancyEpisode',
        'scope' => 'mch',
        'modules' => $modules,
        'make' => fn (TestCase $test, Branch $branch, array $attributes = []) => mchApiConsentedEpisode($branch),
        'update' => fn (TestCase $test, $record): array => ['gravida' => 3],
    ]);
});

describe('growth measurements', function () use ($modules): void {
    ApiResourceTestSuite::register([
        'uri' => 'growth-measurements',
        'subject' => 'GrowthMeasurement',
        'scope' => 'mch',
        'modules' => $modules,
        'make' => function (TestCase $test, Branch $branch, array $attributes = []) {
            $record = mchApiConsentedChild($branch);

            return GrowthMeasurement::factory()->create(['patient_id' => $record->patient_id, 'branch_id' => $branch->id, ...$attributes]);
        },
        'store' => fn (TestCase $test, Branch $branch): array => ['patient_id' => mchApiConsentedChild($branch)->patient_id, 'type' => 'weight', 'value' => 8.2, 'unit' => 'kg'],
        'invalid' => [['type' => 'shoe_size', 'value' => 1, 'unit' => 'cm'], 'type'],
    ]);
});

describe('immunizations', function () use ($modules): void {
    ApiResourceTestSuite::register([
        'uri' => 'immunizations',
        'subject' => 'ImmunizationRecord',
        'scope' => 'mch',
        'modules' => $modules,
        'make' => function (TestCase $test, Branch $branch, array $attributes = []) {
            $record = mchApiConsentedChild($branch);

            return ImmunizationRecord::factory()->create(['patient_id' => $record->patient_id, 'branch_id' => $branch->id, ...$attributes]);
        },
    ]);
});

describe('vaccines', function () use ($modules): void {
    ApiResourceTestSuite::register([
        'uri' => 'vaccines',
        'subject' => 'Vaccine',
        'scope' => 'mch',
        'modules' => $modules,
        'branchIsolated' => false,
        'make' => fn (TestCase $test, Branch $branch, array $attributes = []) => Vaccine::factory()->create($attributes),
    ]);
});

describe('mch books', function () use ($modules): void {
    ApiResourceTestSuite::register([
        'uri' => 'mch-books',
        'subject' => 'MchRecord',
        'scope' => 'mch',
        'modules' => $modules,
        'make' => fn (TestCase $test, Branch $branch, array $attributes = []) => MchRecord::query()->where('owner_id', mchApiConsentedEpisode($branch)->id)->sole(),
    ]);
});

describe('mch workflow', function () use ($modules): void {
    beforeEach(function () use ($modules): void {
        $this->requireModule('Api');
        $this->migrateModules($modules);
        $this->branch = Branch::factory()->create();
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        foreach (['PregnancyEpisode', 'MaternalVisitAssessment', 'ImmunizationRecord', 'DeliveryRecord', 'MchRecord', 'PostnatalVisitAssessment'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update'] as $action) {
                Permission::findOrCreate("{$action} {$subject}", 'web');
                $this->user->givePermissionTo("{$action} {$subject}");
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withToken($this->user->createToken('t', ['mch:write'])->plainTextToken);
    });

    it('hides and refuses clients without consent until consent is recorded', function (): void {
        $episode = mchApiConsentedEpisode($this->branch, consented: false);

        $this->getJson("/api/v1/pregnancies/{$episode->id}")->assertNotFound();
        $this->postJson('/api/v1/anc-visits', ['patient_id' => $episode->patient_id, 'ga_weeks' => 20])->assertForbidden();

        $book = MchRecord::query()->where('owner_id', $episode->id)->sole();
        $this->postJson("/api/v1/mch-books/{$book->id}/record-consent")->assertOk()->assertJsonPath('data.data_consented', true);

        $this->getJson("/api/v1/pregnancies/{$episode->id}")->assertOk();
    });

    it('lets the consent override permission see everything', function (): void {
        $episode = mchApiConsentedEpisode($this->branch, consented: false);
        Permission::findOrCreate(MchConsentGate::OVERRIDE_PERMISSION, 'web');
        $this->user->givePermissionTo(MchConsentGate::OVERRIDE_PERMISSION);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->getJson("/api/v1/pregnancies/{$episode->id}")->assertOk();
    });

    it('records an ANC visit and a twin delivery', function (): void {
        $episode = mchApiConsentedEpisode($this->branch);

        $this->postJson('/api/v1/anc-visits', ['patient_id' => $episode->patient_id, 'ga_weeks' => 32, 'systolic_bp' => 118, 'diastolic_bp' => 76])
            ->assertCreated()
            ->assertJsonPath('data.ga_weeks', 32);

        $this->postJson('/api/v1/deliveries', [
            'pregnancy_episode_id' => $episode->id,
            'delivered_at' => now()->subHour()->toIso8601String(),
            'place' => 'facility',
            'mode' => 'svd',
            'babies' => [
                ['sex' => 'male', 'outcome' => 'live_birth', 'birth_weight_g' => 2500],
                ['sex' => 'female', 'outcome' => 'live_birth', 'birth_weight_g' => 2400],
            ],
        ])->assertCreated()->assertJsonCount(2, 'data.babies');

        expect($episode->fresh()->outcome)->toBe(PregnancyOutcome::DELIVERED);

        $this->postJson('/api/v1/pnc-visits', ['patient_id' => $episode->patient_id, 'breastfeeding' => 'exclusive'])
            ->assertCreated()
            ->assertJsonPath('data.contact', 'day_1');
    });

    it('administers a scheduled dose', function (): void {
        $record = mchApiConsentedChild($this->branch);
        $dose = ImmunizationRecord::factory()->create(['patient_id' => $record->patient_id, 'branch_id' => $this->branch->id, 'status' => ImmunizationStatus::SCHEDULED]);

        $this->postJson("/api/v1/immunizations/{$dose->id}/administer", ['batch_lot' => 'LOT1'])
            ->assertOk()
            ->assertJsonPath('data.status', ImmunizationStatus::ADMINISTERED->value);
        $this->postJson("/api/v1/immunizations/{$dose->id}/administer")->assertUnprocessable();
        $this->postJson("/api/v1/immunizations/{$dose->id}/decline", ['reason' => 'Late refusal'])->assertUnprocessable();
    });
});
