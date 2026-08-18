<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\ImmunizationScheduleResource;

class ViewImmunizationSchedule extends ViewRecord
{
    protected static string $resource = ImmunizationScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
