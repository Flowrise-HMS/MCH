<?php

namespace Modules\MCH\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\Vaccine;

class MchImmunizationSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = collect(VaccineAntigen::cases())->mapWithKeys(fn (VaccineAntigen $antigen) => [
            $antigen->value => Vaccine::firstOrCreate(
                ['antigen' => $antigen],
                ['name' => $antigen->getLabel()],
            ),
        ]);

        $schedule = ImmunizationSchedule::firstOrCreate(
            ['name' => 'Ghana EPI'],
            [
                'description' => 'Ghana Expanded Programme on Immunization — childhood schedule.',
                'target_population' => 'child',
            ],
        );

        $items = [
            ['vaccine' => 'bcg', 'dose' => 1, 'min_days' => 0, 'label' => 'BCG at birth'],
            ['vaccine' => 'opv', 'dose' => 1, 'min_days' => 42, 'label' => 'OPV-1 at 6 weeks'],
            ['vaccine' => 'pentavalent', 'dose' => 1, 'min_days' => 42, 'label' => 'Penta-1 at 6 weeks'],
            ['vaccine' => 'pcv', 'dose' => 1, 'min_days' => 42, 'label' => 'PCV-1 at 6 weeks'],
            ['vaccine' => 'rotavirus', 'dose' => 1, 'min_days' => 42, 'label' => 'Rota-1 at 6 weeks'],
            ['vaccine' => 'opv', 'dose' => 2, 'min_days' => 70, 'label' => 'OPV-2 at 10 weeks'],
            ['vaccine' => 'pentavalent', 'dose' => 2, 'min_days' => 70, 'label' => 'Penta-2 at 10 weeks'],
            ['vaccine' => 'pcv', 'dose' => 2, 'min_days' => 70, 'label' => 'PCV-2 at 10 weeks'],
            ['vaccine' => 'rotavirus', 'dose' => 2, 'min_days' => 70, 'label' => 'Rota-2 at 10 weeks'],
            ['vaccine' => 'opv', 'dose' => 3, 'min_days' => 98, 'label' => 'OPV-3 at 14 weeks'],
            ['vaccine' => 'pentavalent', 'dose' => 3, 'min_days' => 98, 'label' => 'Penta-3 at 14 weeks'],
            ['vaccine' => 'pcv', 'dose' => 3, 'min_days' => 98, 'label' => 'PCV-3 at 14 weeks'],
            ['vaccine' => 'rotavirus', 'dose' => 3, 'min_days' => 98, 'label' => 'Rota-3 at 14 weeks'],
            ['vaccine' => 'measles_rubella', 'dose' => 1, 'min_days' => 270, 'label' => 'MR-1 at 9 months'],
            ['vaccine' => 'yellow_fever', 'dose' => 1, 'min_days' => 270, 'label' => 'Yellow Fever at 9 months'],
            ['vaccine' => 'measles_rubella', 'dose' => 2, 'min_days' => 540, 'label' => 'MR-2 at 18 months'],
        ];

        foreach ($items as $item) {
            ImmunizationScheduleItem::firstOrCreate(
                [
                    'immunization_schedule_id' => $schedule->id,
                    'vaccine_id' => $vaccines[$item['vaccine']]->id,
                    'dose_sequence' => $item['dose'],
                ],
                [
                    'minimum_age_days' => $item['min_days'],
                    'label' => $item['label'],
                ],
            );
        }
    }
}
