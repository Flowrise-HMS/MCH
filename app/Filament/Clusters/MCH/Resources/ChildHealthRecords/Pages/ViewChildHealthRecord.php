<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\ChildHealthRecordResource;

class ViewChildHealthRecord extends ViewRecord
{
    protected static string $resource = ChildHealthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
