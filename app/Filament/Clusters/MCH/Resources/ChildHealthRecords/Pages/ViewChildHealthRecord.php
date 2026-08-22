<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Pages\VaccinationCard;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\ChildHealthRecordResource;

class ViewChildHealthRecord extends ViewRecord
{
    protected static string $resource = ChildHealthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('vaccinationCard')
                ->label('Vaccination card')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => VaccinationCard::getUrl([
                    'patientId' => $this->record->patient_id,
                ]))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
