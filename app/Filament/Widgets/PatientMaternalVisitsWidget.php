<?php

namespace Modules\MCH\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Tables\MaternalVisitAssessmentsTable;
use Modules\MCH\Models\MaternalVisitAssessment;

class PatientMaternalVisitsWidget extends BaseTableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'ANC visit history';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $patientId = null;

    protected function getTableQuery(): Builder
    {
        return MaternalVisitAssessment::query()
            ->when(
                filled($this->patientId),
                fn (Builder $query): Builder => $query->where('patient_id', $this->patientId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->orderByDesc('created_at');
    }

    protected function getTableColumns(): array
    {
        return MaternalVisitAssessmentsTable::columns(includePatient: false);
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No ANC visits recorded';
    }

    protected function getTablePollingInterval(): ?string
    {
        return null;
    }
}
