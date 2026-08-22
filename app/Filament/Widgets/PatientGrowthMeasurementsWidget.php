<?php

namespace Modules\MCH\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Tables\GrowthMeasurementsTable;
use Modules\MCH\Models\GrowthMeasurement;

class PatientGrowthMeasurementsWidget extends BaseTableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Growth measurements';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $patientId = null;

    protected function getTableQuery(): Builder
    {
        return GrowthMeasurement::query()
            ->when(
                filled($this->patientId),
                fn (Builder $query): Builder => $query->where('patient_id', $this->patientId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->orderByDesc('date');
    }

    protected function getTableColumns(): array
    {
        return GrowthMeasurementsTable::columns(includePatient: false);
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No growth measurements';
    }

    protected function getTablePollingInterval(): ?string
    {
        return null;
    }
}
