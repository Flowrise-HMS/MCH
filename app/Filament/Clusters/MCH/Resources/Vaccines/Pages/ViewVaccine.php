<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\VaccineResource;

class ViewVaccine extends ViewRecord
{
    protected static string $resource = VaccineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
