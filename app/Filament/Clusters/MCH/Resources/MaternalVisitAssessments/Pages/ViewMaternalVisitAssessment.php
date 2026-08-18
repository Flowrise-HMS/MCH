<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\MaternalVisitAssessmentResource;

class ViewMaternalVisitAssessment extends ViewRecord
{
    protected static string $resource = MaternalVisitAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
