<?php

namespace Modules\MCH\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Core\Enums\Title;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\EpiDueService;
use Modules\MCH\Classes\Services\ImmunizationRecordService;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Enums\EducationLevel;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Enums\MaritalStatus;
use Modules\Patient\Models\Patient;

class MchImmunizationDemoSeeder extends Seeder
{
    public function __construct(
        private EpiDueService $epiDueService,
        private ImmunizationRecordService $immunizationRecordService,
    ) {}

    public function run(): void
    {
        $this->call(MchImmunizationSeeder::class);

        $branch = Branch::query()->first() ?? Branch::factory()->create([
            'name' => 'Main',
        ]);
        $recordedBy = User::query()->value('id');
        $schedule = ImmunizationSchedule::query()->where('name', 'Ghana EPI')->firstOrFail();

        $ama = $this->child($branch, [
            'mrn' => 'MCH-EPI-AMA',
            'first_name' => 'Ama',
            'last_name' => 'Boateng',
            'gender' => Gender::FEMALE,
            'days_old' => 5,
        ]);
        $this->generateDue($ama, $schedule, $branch->id);
        $this->administer($ama, VaccineAntigen::BCG, 1, 'BCG-DEMO-001', $recordedBy);

        $kwame = $this->child($branch, [
            'mrn' => 'MCH-EPI-KWAME',
            'first_name' => 'Kwame',
            'last_name' => 'Asante',
            'gender' => Gender::MALE,
            'days_old' => 77,
        ]);
        $this->generateDue($kwame, $schedule, $branch->id);
        $this->administer($kwame, VaccineAntigen::BCG, 1, 'BCG-DEMO-002', $recordedBy);
        foreach ([VaccineAntigen::OPV, VaccineAntigen::PENTAVALENT, VaccineAntigen::PCV, VaccineAntigen::ROTAVIRUS] as $antigen) {
            $this->administer($kwame, $antigen, 1, 'WK6-DEMO-001', $recordedBy);
        }

        $abena = $this->child($branch, [
            'mrn' => 'MCH-EPI-ABENA',
            'first_name' => 'Abena',
            'last_name' => 'Owusu',
            'gender' => Gender::FEMALE,
            'days_old' => 330,
        ]);
        $this->generateDue($abena, $schedule, $branch->id);
        $this->administerAllDueExcept($abena, VaccineAntigen::YELLOW_FEVER, $recordedBy);

        $yaw = $this->child($branch, [
            'mrn' => 'MCH-EPI-YAW',
            'first_name' => 'Yaw',
            'last_name' => 'Mensah',
            'gender' => Gender::MALE,
            'days_old' => 56,
        ]);
        $this->generateDue($yaw, $schedule, $branch->id);
        $this->administer($yaw, VaccineAntigen::BCG, 1, 'BCG-DEMO-003', $recordedBy);
        $this->decline($yaw, VaccineAntigen::OPV, 1, 'Guardian declined oral polio at CWC.');
    }

    /**
     * @param  array{mrn: string, first_name: string, last_name: string, gender: Gender, days_old: int}  $attrs
     */
    private function child(Branch $branch, array $attrs): Patient
    {
        $child = Patient::query()->firstOrCreate(
            ['mrn' => $attrs['mrn']],
            [
                'branch_id' => $branch->id,
                'title' => Title::MASTERS,
                'first_name' => $attrs['first_name'],
                'last_name' => $attrs['last_name'],
                'gender' => $attrs['gender'],
                'date_of_birth' => now()->subDays($attrs['days_old'])->toDateString(),
                'marital_status' => MaritalStatus::SINGLE,
                'education_level' => EducationLevel::PRIMARY,
                'nationality' => 'GH',
                'is_active' => true,
            ],
        );

        ChildHealthRecord::query()->withoutGlobalScopes()->firstOrCreate(
            ['patient_id' => $child->id],
            [
                'branch_id' => $branch->id,
                'date_of_birth' => $child->date_of_birth?->toDateString() ?? now()->subDays($attrs['days_old'])->toDateString(),
            ],
        );

        return $child;
    }

    private function generateDue(Patient $child, ImmunizationSchedule $schedule, string $branchId): void
    {
        $this->epiDueService->generateDueRecords($child, $schedule, $branchId);
    }

    private function administer(
        Patient $child,
        VaccineAntigen $antigen,
        int $dose,
        string $batchLot,
        mixed $recordedBy,
    ): void {
        $record = $this->scheduledRecord($child, $antigen, $dose);

        if ($record === null) {
            return;
        }

        $this->immunizationRecordService->administer($record, [
            'administered_date' => now()->toDateString(),
            'batch_lot' => $batchLot,
            'site' => $antigen === VaccineAntigen::OPV || $antigen === VaccineAntigen::ROTAVIRUS ? 'mouth' : 'left_upper_arm',
            'route' => $antigen === VaccineAntigen::OPV || $antigen === VaccineAntigen::ROTAVIRUS ? 'oral' : 'intramuscular',
        ]);

        if ($recordedBy !== null) {
            $record->forceFill(['recorded_by' => $recordedBy])->save();
        }
    }

    private function administerAllDueExcept(Patient $child, VaccineAntigen $skip, mixed $recordedBy): void
    {
        $skipVaccineId = Vaccine::query()->where('antigen', $skip)->value('id');

        $records = ImmunizationRecord::query()
            ->where('patient_id', $child->id)
            ->where('status', ImmunizationStatus::SCHEDULED)
            ->when($skipVaccineId, fn ($query) => $query->where('vaccine_id', '!=', $skipVaccineId))
            ->get();

        foreach ($records as $index => $record) {
            $this->immunizationRecordService->administer($record, [
                'administered_date' => now()->subDays(min(14, $index))->toDateString(),
                'batch_lot' => 'EPI-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
            ]);

            if ($recordedBy !== null) {
                $record->forceFill(['recorded_by' => $recordedBy])->save();
            }
        }
    }

    private function decline(Patient $child, VaccineAntigen $antigen, int $dose, string $reason): void
    {
        $record = $this->scheduledRecord($child, $antigen, $dose);

        if ($record === null) {
            return;
        }

        $this->immunizationRecordService->decline($record, ['reason' => $reason]);
    }

    private function scheduledRecord(Patient $child, VaccineAntigen $antigen, int $dose): ?ImmunizationRecord
    {
        $vaccineId = Vaccine::query()->where('antigen', $antigen)->value('id');

        if ($vaccineId === null) {
            return null;
        }

        return ImmunizationRecord::query()
            ->where('patient_id', $child->id)
            ->where('vaccine_id', $vaccineId)
            ->where('dose_sequence', $dose)
            ->where('status', ImmunizationStatus::SCHEDULED)
            ->first();
    }
}
