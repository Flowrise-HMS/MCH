<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\ImmunizationScheduleResource;

class CreateImmunizationSchedule extends CreateRecord
{
    protected static string $resource = ImmunizationScheduleResource::class;
}
