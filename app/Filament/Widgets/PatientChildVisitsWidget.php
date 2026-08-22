<?php

namespace Modules\MCH\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Tables\ChildVisitAssessmentsTable;
use Modules\MCH\Models\ChildVisitAssessment;

class PatientChildVisitsWidget extends BaseTableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'CWC visit history';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $patientId = null;

    protected function getTableQuery(): Builder
    {
        return ChildVisitAssessment::query()
            ->when(
                filled($this->patientId),
                fn (Builder $query): Builder => $query->where('patient_id', $this->patientId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->orderByDesc('created_at');
    }

    protected function getTableColumns(): array
    {
        return ChildVisitAssessmentsTable::columns(includePatient: false);
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No CWC visits recorded';
    }

    protected function getTablePollingInterval(): ?string
    {
        return null;
    }
}
