<?php

namespace Modules\MCH\Filament\RelationManagers\Patient;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Tables\ImmunizationRecordsTable;

/**
 * Read-only view of a patient's immunizations on the Patient resource.
 * Administer/decline transitions stay on the MCH workspace and cluster.
 */
class PatientImmunizationRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'immunizationRecords';

    protected static ?string $title = 'Immunizations';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('vaccine'))
            ->columns(ImmunizationRecordsTable::columns(includePatient: false))
            ->defaultSort('created_at', 'desc');
    }
}
