<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\ImmunizationScheduleResource;

class EditImmunizationSchedule extends EditRecord
{
    protected static string $resource = ImmunizationScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
