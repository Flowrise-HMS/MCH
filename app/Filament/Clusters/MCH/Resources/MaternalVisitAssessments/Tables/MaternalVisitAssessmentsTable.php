<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaternalVisitAssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')->label('Mother')->searchable(['patient.mrn']),
                TextColumn::make('visit_number')->label('Visit #')->sortable(),
                TextColumn::make('ga_weeks')->label('GA'),
                TextColumn::make('fetal_heart_rate')->label('FHR'),
                TextColumn::make('presentation')->badge(),
                IconColumn::make('referral_required')->boolean()->label('Referral'),
                TextColumn::make('return_date')->date(),
                TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
