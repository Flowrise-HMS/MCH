<?php

namespace Modules\MCH\Filament\Clusters\MCH\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Classes\Services\EpiDueService;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\Patient\Models\Patient;

class VaccinationCard extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'Vaccination card';

    protected static ?string $slug = 'vaccination-card';

    protected static ?int $navigationSort = 13;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'mch::filament.pages.vaccination-card';

    #[Url]
    public ?string $patientId = null;

    public function getTitle(): string|Htmlable
    {
        return 'Vaccination card';
    }

    public function getHeading(): string|Htmlable
    {
        $patient = $this->patient();

        if ($patient === null) {
            return 'Vaccination card';
        }

        return 'Vaccination card — '.$patient->full_name;
    }

    public function patient(): ?Patient
    {
        if ($this->patientId === null || $this->patientId === '') {
            return null;
        }

        return Patient::query()->find($this->patientId);
    }

    /**
     * One row per active child-schedule dose, merged with the child's records.
     * Doses without a record are classified by EpiDueService so due and overdue
     * doses print on the card; records outside the schedule are appended.
     *
     * @return Collection<int, array{vaccine: string, dose: int, status: string, date: ?string, batch_lot: ?string, classification: ?string}>
     */
    public function rows(): Collection
    {
        $patient = $this->patient();

        if ($patient === null) {
            return collect();
        }

        $records = ImmunizationRecord::query()
            ->with('vaccine')
            ->where('patient_id', $patient->id)
            ->orderBy('administered_date')
            ->orderBy('dose_sequence')
            ->get();

        $recordsByKey = $records
            ->sortByDesc(fn (ImmunizationRecord $record): int => $record->status === ImmunizationStatus::ADMINISTERED ? 1 : 0)
            ->keyBy(fn (ImmunizationRecord $record): string => $record->vaccine_id.'|'.$record->dose_sequence);

        $items = ImmunizationSchedule::query()
            ->where('is_active', true)
            ->where('target_population', 'child')
            ->first()
            ?->items()
            ->with('vaccine')
            ->orderBy('minimum_age_days')
            ->orderBy('dose_sequence')
            ->get() ?? collect();

        $epiDueService = app(EpiDueService::class);
        $dob = $patient->date_of_birth === null ? null : $epiDueService->getDateOfBirth($patient);
        $rows = collect();
        $seen = [];

        foreach ($items as $item) {
            /** @var ImmunizationScheduleItem $item */
            $key = $item->vaccine_id.'|'.$item->dose_sequence;
            $seen[$key] = true;
            $record = $recordsByKey->get($key);

            if ($record !== null) {
                $rows->push($this->rowFromRecord($record));

                continue;
            }

            $rows->push([
                'vaccine' => $item->vaccine?->name ?? $item->label ?? '—',
                'dose' => (int) $item->dose_sequence,
                'status' => 'Not given',
                'date' => null,
                'batch_lot' => null,
                'classification' => $dob === null ? null : $epiDueService->classifyScheduledDose($dob, $item),
            ]);
        }

        foreach ($records as $record) {
            if (! isset($seen[$record->vaccine_id.'|'.$record->dose_sequence])) {
                $rows->push($this->rowFromRecord($record));
            }
        }

        return $rows;
    }

    /**
     * @return array{vaccine: string, dose: int, status: string, date: ?string, batch_lot: ?string, classification: ?string}
     */
    private function rowFromRecord(ImmunizationRecord $record): array
    {
        return [
            'vaccine' => $record->vaccine?->name ?? '—',
            'dose' => (int) $record->dose_sequence,
            'status' => $record->status?->getLabel() ?? (string) $record->status,
            'date' => $record->administered_date?->toDateString(),
            'batch_lot' => $record->batch_lot,
            'classification' => null,
        ];
    }
}
