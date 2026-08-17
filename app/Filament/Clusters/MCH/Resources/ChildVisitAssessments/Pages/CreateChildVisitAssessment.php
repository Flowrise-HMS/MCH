<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Classes\Services\ChildVisitAssessmentService;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\ChildVisitAssessmentResource;

class CreateChildVisitAssessment extends CreateRecord
{
    protected static string $resource = ChildVisitAssessmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $encounter = Encounter::query()->findOrFail($data['encounter_id']);
        $data['recorded_by'] = Auth::id();

        return app(ChildVisitAssessmentService::class)->record($encounter, $data);
    }
}
