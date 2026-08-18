<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class ImmunizationRecordTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
    }

    public function test_records_administered_immunization(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);
        $vaccine = Vaccine::create([
            'antigen' => VaccineAntigen::BCG,
            'name' => 'BCG',
        ]);

        $record = ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::ADMINISTERED,
            'administered_date' => now()->toDateString(),
            'batch_lot' => 'BCG-2026-001',
        ]);

        $this->assertSame(ImmunizationStatus::ADMINISTERED, $record->status);
        $this->assertSame($vaccine->id, $record->vaccine_id);
        $this->assertSame(1, $record->dose_sequence);
    }

    public function test_records_declined_immunization(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);
        $vaccine = Vaccine::factory()->create();

        $record = ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::DECLINED,
            'reason' => 'Parental refusal',
        ]);

        $this->assertSame(ImmunizationStatus::DECLINED, $record->status);
        $this->assertSame('Parental refusal', $record->reason);
    }

    public function test_same_vaccine_different_doses_are_allowed(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);
        $vaccine = Vaccine::factory()->create();

        ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::ADMINISTERED,
            'administered_date' => now()->toDateString(),
        ]);

        $second = ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 2,
            'status' => ImmunizationStatus::ADMINISTERED,
            'administered_date' => now()->addWeeks(4)->toDateString(),
        ]);

        $this->assertNotNull($second->id);
    }

    public function test_record_belongs_to_vaccine(): void
    {
        $record = ImmunizationRecord::factory()->create();
        $this->assertInstanceOf(Vaccine::class, $record->vaccine);
    }

    public function test_record_belongs_to_patient(): void
    {
        $record = ImmunizationRecord::factory()->create();
        $this->assertInstanceOf(Patient::class, $record->patient);
    }
}
