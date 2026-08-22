<?php

namespace Modules\MCH\Filament\Clusters\MCH\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\Patient\Models\Patient;

class VaccinationCard extends Page
{
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
     * @return Collection<int, ImmunizationRecord>
     */
    public function records()
    {
        $patient = $this->patient();

        if ($patient === null) {
            return ImmunizationRecord::query()->whereRaw('1 = 0')->get();
        }

        return ImmunizationRecord::query()
            ->with('vaccine')
            ->where('patient_id', $patient->id)
            ->orderBy('administered_date')
            ->orderBy('dose_sequence')
            ->get();
    }
}
