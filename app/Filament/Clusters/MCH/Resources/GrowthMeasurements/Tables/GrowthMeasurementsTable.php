<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Enums\GrowthMeasurementType;

class GrowthMeasurementsTable
{
    /**
     * @return array<int, TextColumn>
     */
    public static function columns(bool $includePatient = true): array
    {
        return [
            ...($includePatient ? [ClientIdentityColumn::make(label: 'Patient')] : []),
            TextColumn::make('type')->badge(),
            TextColumn::make('value'),
            TextColumn::make('unit'),
            TextColumn::make('date')->date()->sortable(),
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters([
                SelectFilter::make('type')->options(GrowthMeasurementType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }
}
