<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChildVisitAssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')->label('Child')->searchable(['patient.mrn']),
                TextColumn::make('feeding')->badge(),
                IconColumn::make('vitamin_a_given')->boolean()->label('Vit A'),
                IconColumn::make('dewormed')->boolean(),
                TextColumn::make('developmental_screen')->badge(),
                IconColumn::make('referral_required')->boolean()->label('Referral'),
                TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
