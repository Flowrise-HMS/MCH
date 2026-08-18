<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\ImmunizationRecordResource;

class ViewImmunizationRecord extends ViewRecord
{
    protected static string $resource = ImmunizationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
