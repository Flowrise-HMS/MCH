<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\PregnancyEpisodeResource;

class ViewPregnancyEpisode extends ViewRecord
{
    protected static string $resource = PregnancyEpisodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
