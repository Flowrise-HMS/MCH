<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\MaternalVisitAssessmentResource;

class ListMaternalVisitAssessments extends ListRecords
{
    protected static string $resource = MaternalVisitAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
