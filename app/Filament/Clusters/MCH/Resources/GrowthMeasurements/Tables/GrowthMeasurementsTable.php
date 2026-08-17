<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\MCH\Enums\GrowthMeasurementType;

class GrowthMeasurementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')->label('Patient')->searchable(['patient.mrn']),
                TextColumn::make('type')->badge(),
                TextColumn::make('value'),
                TextColumn::make('unit'),
                TextColumn::make('date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(GrowthMeasurementType::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }
}
