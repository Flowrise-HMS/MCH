<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\GrowthMeasurementResource;

class EditGrowthMeasurement extends EditRecord
{
    protected static string $resource = GrowthMeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
