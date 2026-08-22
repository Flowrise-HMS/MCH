<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;

class ChildVisitAssessmentsTable
{
    /**
     * @return array<int, TextColumn|IconColumn>
     */
    public static function columns(bool $includePatient = true): array
    {
        return [
            ...($includePatient ? [ClientIdentityColumn::make(label: 'Child')] : []),
            TextColumn::make('feeding')->badge(),
            IconColumn::make('vitamin_a_given')->boolean()->label('Vit A'),
            IconColumn::make('dewormed')->boolean(),
            TextColumn::make('developmental_screen')->badge(),
            IconColumn::make('referral_required')->boolean()->label('Referral'),
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
