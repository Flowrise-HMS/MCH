<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Enums\ChildHealthRecordStatus;

class ChildHealthRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ClientIdentityColumn::make(label: 'Child'),
                TextColumn::make('date_of_birth')->date()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('pregnancyEpisode.id')->label('Pregnancy episode')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(ChildHealthRecordStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
