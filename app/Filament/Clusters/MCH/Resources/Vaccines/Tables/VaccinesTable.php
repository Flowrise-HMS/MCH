<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VaccinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('antigen')->badge(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('route'),
                TextColumn::make('site'),
                TextColumn::make('presentation'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
