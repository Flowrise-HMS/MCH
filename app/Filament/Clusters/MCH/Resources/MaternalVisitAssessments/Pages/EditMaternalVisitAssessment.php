<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\MaternalVisitAssessmentResource;

class EditMaternalVisitAssessment extends EditRecord
{
    protected static string $resource = MaternalVisitAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
