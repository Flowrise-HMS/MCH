<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\VaccineResource;

class ListVaccines extends ListRecords
{
    protected static string $resource = VaccineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
