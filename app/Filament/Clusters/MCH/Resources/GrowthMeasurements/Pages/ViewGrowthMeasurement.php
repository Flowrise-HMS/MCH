<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\GrowthMeasurementResource;

class ViewGrowthMeasurement extends ViewRecord
{
    protected static string $resource = GrowthMeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
