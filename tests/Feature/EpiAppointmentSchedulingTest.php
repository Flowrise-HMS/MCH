<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Modules\Appointment\Models\Appointment;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\EpiAppointmentScheduler;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class EpiAppointmentSchedulingTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private ImmunizationSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
        $this->schedule = $this->createChildSchedule();
    }

    private function createChildSchedule(): ImmunizationSchedule
    {
        $bcg = Vaccine::create(['antigen' => VaccineAntigen::BCG, 'name' => 'BCG']);
        $opv = Vaccine::create(['antigen' => VaccineAntigen::OPV, 'name' => 'OPV']);

        $schedule = ImmunizationSchedule::create([
            'name' => 'Ghana EPI Test',
            'target_population' => 'child',
        ]);

        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $bcg->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
            'label' => 'BCG at birth',
        ]);

        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $opv->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 42,
            'label' => 'OPV-1 at 6 weeks',
        ]);

        return $schedule;
    }

    private function child(int $ageDays): Patient
    {
        return Patient::factory()->child()->create([
            'branch_id' => $this->branch->id,
            'date_of_birth' => Carbon::today()->subDays($ageDays)->toDateString(),
        ]);
    }

    private function scheduledRecord(Patient $child, VaccineAntigen $antigen): ImmunizationRecord
    {
        $vaccine = Vaccine::where('antigen', $antigen)->firstOrFail();

        return ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::SCHEDULED,
        ]);
    }

    public function test_creates_appointment_on_the_due_date_for_a_future_dose(): void
    {
        $child = $this->child(35);
        $record = $this->scheduledRecord($child, VaccineAntigen::OPV);

        $appointment = app(EpiAppointmentScheduler::class)->schedule($record);

        $this->assertNotNull($appointment);
        $this->assertSame(
            Carbon::today()->subDays(35)->addDays(42)->toDateString(),
            $appointment->start_at->toDateString(),
        );
        $this->assertSame('09:00', $appointment->start_at->format('H:i'));
        $this->assertSame('epi-dose:'.$record->id, $appointment->external_reference);
        $this->assertSame($child->id, $appointment->patient_id);
        $this->assertSame($this->branch->id, $appointment->branch_id);
    }

    public function test_books_overdue_dose_for_today(): void
    {
        $child = $this->child(50);
        $record = $this->scheduledRecord($child, VaccineAntigen::BCG);

        $appointment = app(EpiAppointmentScheduler::class)->schedule($record);

        $this->assertNotNull($appointment);
        $this->assertSame(
            Carbon::today()->toDateString(),
            $appointment->start_at->toDateString(),
        );
    }

    public function test_scheduling_is_idempotent_per_record(): void
    {
        $child = $this->child(35);
        $record = $this->scheduledRecord($child, VaccineAntigen::OPV);
        $scheduler = app(EpiAppointmentScheduler::class);

        $first = $scheduler->schedule($record);
        $second = $scheduler->schedule($record);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(
            1,
            Appointment::where('external_reference', 'epi-dose:'.$record->id)->count(),
        );
    }

    public function test_returns_null_when_record_is_already_administered(): void
    {
        $child = $this->child(35);
        $record = $this->scheduledRecord($child, VaccineAntigen::OPV);
        $record->forceFill(['status' => ImmunizationStatus::ADMINISTERED])->save();

        $this->assertNull(app(EpiAppointmentScheduler::class)->schedule($record));
    }

    public function test_returns_null_when_no_active_child_schedule_item_matches(): void
    {
        $child = $this->child(35);
        $orphanVaccine = Vaccine::create([
            'antigen' => VaccineAntigen::YELLOW_FEVER,
            'name' => 'Yellow Fever',
        ]);
        $record = ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
            'vaccine_id' => $orphanVaccine->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::SCHEDULED,
        ]);

        $this->assertNull(app(EpiAppointmentScheduler::class)->schedule($record));
    }
}
