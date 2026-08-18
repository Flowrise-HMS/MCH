<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\MchRecordResource;

class ViewMchRecord extends ViewRecord
{
    protected static string $resource = MchRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
