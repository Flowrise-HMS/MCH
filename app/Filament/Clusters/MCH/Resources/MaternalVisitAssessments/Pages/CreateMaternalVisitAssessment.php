<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Classes\Services\MaternalVisitAssessmentService;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\MaternalVisitAssessmentResource;

class CreateMaternalVisitAssessment extends CreateRecord
{
    protected static string $resource = MaternalVisitAssessmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $encounter = Encounter::query()->findOrFail($data['encounter_id']);
        $data['recorded_by'] = Auth::id();
        $data['fundal_height_unit'] = 'cm';

        return app(MaternalVisitAssessmentService::class)->record($encounter, $data);
    }
}
