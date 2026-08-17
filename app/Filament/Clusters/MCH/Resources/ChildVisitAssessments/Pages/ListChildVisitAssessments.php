<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\ChildVisitAssessmentResource;

class ListChildVisitAssessments extends ListRecords
{
    protected static string $resource = ChildVisitAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
