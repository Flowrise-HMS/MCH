<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\Vaccine;

class ImmunizationRecordsTable
{
    /**
     * @return array<int, TextColumn>
     */
    public static function columns(bool $includePatient = true): array
    {
        return [
            ...($includePatient ? [ClientIdentityColumn::make(label: 'Patient')] : []),
            TextColumn::make('vaccine.name')->label('Vaccine')->searchable(),
            TextColumn::make('dose_sequence')->label('Dose'),
            TextColumn::make('status')->badge(),
            TextColumn::make('administered_date')->date()->sortable(),
            TextColumn::make('batch_lot')->searchable()->toggleable(),
            TextColumn::make('recorded_by')->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters([
                SelectFilter::make('status')->options(ImmunizationStatus::class),
                SelectFilter::make('vaccine_id')
                    ->label('Vaccine')
                    ->options(fn (): array => Vaccine::query()->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
