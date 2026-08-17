<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\GrowthMeasurementResource;

class ListGrowthMeasurements extends ListRecords
{
    protected static string $resource = GrowthMeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
