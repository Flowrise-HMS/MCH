<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Classes\Services\GrowthZScoreService;
use Modules\MCH\Enums\GrowthIndicator;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;

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
            TextColumn::make('z_score')
                ->label(__('Z-score'))
                ->badge()
                ->formatStateUsing(fn ($state, GrowthMeasurement $record): string => sprintf('%s %+.2f', $record->indicator()?->abbreviation() ?? '', (float) $state))
                ->color(fn (GrowthMeasurement $record): string => GrowthZScoreService::classify($record->z_indicator, $record->z_score !== null ? (float) $record->z_score : null)['color'] ?? 'gray')
                ->tooltip(fn (GrowthMeasurement $record): ?string => GrowthZScoreService::classify($record->z_indicator, $record->z_score !== null ? (float) $record->z_score : null)['label'] ?? null)
                ->placeholder('—'),
            TextColumn::make('whz')
                ->label(__('WHZ'))
                ->badge()
                ->formatStateUsing(fn ($state): string => sprintf('%+.2f', (float) $state))
                ->color(fn (GrowthMeasurement $record): string => GrowthZScoreService::classify(GrowthIndicator::WEIGHT_FOR_LENGTH, $record->whz !== null ? (float) $record->whz : null)['color'] ?? 'gray')
                ->tooltip(fn (GrowthMeasurement $record): ?string => GrowthZScoreService::classify(GrowthIndicator::WEIGHT_FOR_LENGTH, $record->whz !== null ? (float) $record->whz : null)['label'] ?? null)
                ->placeholder('—'),
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
