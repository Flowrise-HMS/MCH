<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\RiskLevel;

class PregnancyEpisodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ClientIdentityColumn::make(label: 'Mother'),
                TextColumn::make('gravida')->toggleable(),
                TextColumn::make('parity')->toggleable(),
                TextColumn::make('lmp')->date()->toggleable(),
                TextColumn::make('edd')->date()->sortable(),
                TextColumn::make('booking_ga_weeks')->label('Booking GA'),
                TextColumn::make('risk_level')->badge(),
                TextColumn::make('outcome')->badge(),
                TextColumn::make('created_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('risk_level')->options(RiskLevel::class),
                SelectFilter::make('outcome')->options(PregnancyOutcome::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
