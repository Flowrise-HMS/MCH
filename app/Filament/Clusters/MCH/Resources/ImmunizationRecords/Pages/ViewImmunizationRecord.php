<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Classes\Services\ImmunizationRecordService;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\ImmunizationRecordResource;
use Modules\MCH\Models\ImmunizationRecord;

class ViewImmunizationRecord extends ViewRecord
{
    protected static string $resource = ImmunizationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('administer')
                ->label('Administer')
                ->color('success')
                ->visible(fn (): bool => in_array(
                    $this->record->status,
                    [ImmunizationStatus::SCHEDULED, ImmunizationStatus::DECLINED],
                    true,
                ))
                ->form([
                    DatePicker::make('administered_date')->default(now())->required(),
                    TextInput::make('batch_lot'),
                    TextInput::make('site'),
                    TextInput::make('route'),
                ])
                ->action(function (array $data, ImmunizationRecordService $service): void {
                    /** @var ImmunizationRecord $record */
                    $record = $this->record;
                    $service->administer($record, $data);
                    $this->refreshFormData(['status', 'administered_date', 'batch_lot', 'site', 'route', 'reason']);
                    Notification::make()->title('Dose administered')->success()->send();
                }),
            Action::make('decline')
                ->label('Decline')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status === ImmunizationStatus::SCHEDULED)
                ->form([
                    TextInput::make('reason')->required(),
                ])
                ->action(function (array $data, ImmunizationRecordService $service): void {
                    /** @var ImmunizationRecord $record */
                    $record = $this->record;
                    $service->decline($record, $data);
                    $this->refreshFormData(['status', 'administered_date', 'batch_lot', 'reason']);
                    Notification::make()->title('Dose declined')->warning()->send();
                }),
            EditAction::make(),
        ];
    }
}
