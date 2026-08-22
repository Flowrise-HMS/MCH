<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImmunizationSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('target_population')->badge(),
                TextColumn::make('items_count')->counts('items')->label('Doses'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }
}
