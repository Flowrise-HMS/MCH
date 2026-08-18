<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Enums\GrowthMeasurementType;

class GrowthMeasurementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ClientIdentityColumn::make(label: 'Patient'),
                TextColumn::make('type')->badge(),
                TextColumn::make('value'),
                TextColumn::make('unit'),
                TextColumn::make('date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(GrowthMeasurementType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }
}
