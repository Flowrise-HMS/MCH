<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;

class MaternalVisitAssessmentsTable
{
    /**
     * @return array<int, TextColumn|IconColumn>
     */
    public static function columns(bool $includePatient = true): array
    {
        return [
            ...($includePatient ? [ClientIdentityColumn::make(label: 'Mother')] : []),
            TextColumn::make('visit_number')->label('Visit #')->sortable(),
            TextColumn::make('ga_weeks')->label('GA'),
            TextColumn::make('fetal_heart_rate')->label('FHR'),
            TextColumn::make('presentation')->badge(),
            IconColumn::make('referral_required')->boolean()->label('Referral'),
            TextColumn::make('return_date')->date(),
            TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters([
                TernaryFilter::make('referral_required')->label('Referral'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
