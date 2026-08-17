<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\PregnancyEpisodeResource;

class ListPregnancyEpisodes extends ListRecords
{
    protected static string $resource = PregnancyEpisodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
