<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\ChildHealthRecordResource;

class ListChildHealthRecords extends ListRecords
{
    protected static string $resource = ChildHealthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
