<?php

namespace Modules\MCH\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Tables\ImmunizationRecordsTable;
use Modules\MCH\Models\ImmunizationRecord;

class PatientImmunizationsWidget extends BaseTableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Immunization records';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $patientId = null;

    protected function getTableQuery(): Builder
    {
        return ImmunizationRecord::query()
            ->with('vaccine')
            ->when(
                filled($this->patientId),
                fn (Builder $query): Builder => $query->where('patient_id', $this->patientId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->orderByDesc('created_at');
    }

    protected function getTableColumns(): array
    {
        return ImmunizationRecordsTable::columns(includePatient: false);
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No immunization records';
    }

    protected function getTablePollingInterval(): ?string
    {
        return null;
    }
}
