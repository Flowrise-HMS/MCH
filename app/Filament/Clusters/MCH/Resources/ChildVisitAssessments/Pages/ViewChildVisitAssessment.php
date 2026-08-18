<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\ChildVisitAssessmentResource;

class ViewChildVisitAssessment extends ViewRecord
{
    protected static string $resource = ChildVisitAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
