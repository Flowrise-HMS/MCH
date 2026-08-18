<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\ImmunizationRecordResource;

class EditImmunizationRecord extends EditRecord
{
    protected static string $resource = ImmunizationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
