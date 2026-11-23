<?php

namespace Modules\MCH\Filament\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Modules\MCH\Models\PostnatalVisitAssessment;

class PatientPostnatalVisitsWidget extends BaseTableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Postnatal visits';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $patientId = null;

    #[On('mch-patient-data-updated')]
    public function refreshFromWorkspace(): void {}

    protected function getTableQuery(): Builder
    {
        return PostnatalVisitAssessment::query()
            ->when(
                filled($this->patientId),
                fn (Builder $query): Builder => $query->where('patient_id', $this->patientId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('created_at')->label(__('Date'))->date(),
            TextColumn::make('contact')->label(__('Contact'))->badge(),
            TextColumn::make('days_postpartum')->label(__('Day'))->placeholder('—'),
            TextColumn::make('subject')->label(__('For'))->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextColumn::make('breastfeeding')->label(__('Breastfeeding'))->placeholder('—'),
            TextColumn::make('danger_signs')
                ->label(__('Danger signs'))
                ->badge()
                ->color('danger')
                ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                ->placeholder(__('None')),
            IconColumn::make('referral_required')->label(__('Referred'))->boolean(),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return __('No postnatal visits');
    }
}
