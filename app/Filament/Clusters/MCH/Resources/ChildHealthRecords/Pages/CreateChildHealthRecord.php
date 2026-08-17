<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\ChildHealthRecordResource;

class CreateChildHealthRecord extends CreateRecord
{
    protected static string $resource = ChildHealthRecordResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = Auth::id();

        return $data;
    }
}
