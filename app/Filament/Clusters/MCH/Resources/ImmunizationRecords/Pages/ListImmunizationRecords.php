<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\ImmunizationRecordResource;

class ListImmunizationRecords extends ListRecords
{
    protected static string $resource = ImmunizationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
