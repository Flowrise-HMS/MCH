<?php

namespace Modules\MCH\Filament\RelationManagers\Patient;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Tables\PregnancyEpisodesTable;

/**
 * Read-only view of a patient's pregnancy registry on the Patient resource.
 * Writes stay on the MCH cluster and workspace so registry rules apply.
 */
class PatientPregnancyEpisodesRelationManager extends RelationManager
{
    protected static string $relationship = 'pregnancyEpisodes';

    protected static ?string $title = 'Pregnancies';

    public function table(Table $table): Table
    {
        return $table
            ->columns(PregnancyEpisodesTable::columns(includePatient: false))
            ->defaultSort('created_at', 'desc');
    }
}
