<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\PregnancyEpisodeResource;

class CreatePregnancyEpisode extends CreateRecord
{
    protected static string $resource = PregnancyEpisodeResource::class;

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
