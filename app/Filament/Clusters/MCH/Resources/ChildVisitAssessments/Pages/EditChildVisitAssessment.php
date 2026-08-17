<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\ChildVisitAssessmentResource;

class EditChildVisitAssessment extends EditRecord
{
    protected static string $resource = ChildVisitAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
