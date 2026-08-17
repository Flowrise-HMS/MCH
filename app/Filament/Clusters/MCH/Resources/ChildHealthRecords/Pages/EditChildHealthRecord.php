<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\ChildHealthRecordResource;

class EditChildHealthRecord extends EditRecord
{
    protected static string $resource = ChildHealthRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
