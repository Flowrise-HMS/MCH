<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\MCH\Enums\ChildHealthRecordStatus;

class ChildHealthRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')->label('Child')->searchable(['patient.mrn']),
                TextColumn::make('date_of_birth')->date()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('pregnancyEpisode.id')->label('Pregnancy episode')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(ChildHealthRecordStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
