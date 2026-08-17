<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\MchRecordResource;

class ListMchRecords extends ListRecords
{
    protected static string $resource = MchRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Issue book'),
        ];
    }
}
