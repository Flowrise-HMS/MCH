<?php

namespace Modules\MCH\Filament\RelationManagers\Patient;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Tables\GrowthMeasurementsTable;

/**
 * Read-only view of a patient's anthropometry and fundal height on the Patient resource.
 */
class PatientGrowthMeasurementsRelationManager extends RelationManager
{
    protected static string $relationship = 'growthMeasurements';

    protected static ?string $title = 'Growth measurements';

    public function table(Table $table): Table
    {
        return $table
            ->columns(GrowthMeasurementsTable::columns(includePatient: false))
            ->defaultSort('date', 'desc');
    }
}
