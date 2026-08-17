<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\PregnancyEpisodeResource;

class EditPregnancyEpisode extends EditRecord
{
    protected static string $resource = PregnancyEpisodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
