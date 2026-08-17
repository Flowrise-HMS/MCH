<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\MCH\Enums\EddSource;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;

class PregnancyEpisodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mother')
                ->schema([
                    Select::make('patient_id')
                        ->relationship('patient', 'mrn')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record?->full_name ?? 'Select patient')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Patient'),
                ]),
            Section::make('Obstetric history')
                ->columns(2)
                ->schema([
                    TextInput::make('gravida')->numeric()->minValue(0)->maxValue(30),
                    TextInput::make('parity')->numeric()->minValue(0)->maxValue(30),
                    DatePicker::make('lmp')->label('LMP'),
                    DatePicker::make('edd')->label('EDD'),
                    Select::make('edd_source')->options(EddSource::class)->default(EddSource::LMP->value),
                    Toggle::make('multiple_gestation')->label('Multiple gestation'),
                    DatePicker::make('booking_date'),
                    Select::make('outcome')->options(PregnancyOutcome::class)->default(PregnancyOutcome::ACTIVE->value),
                ]),
            Section::make('Risk')
                ->schema([
                    CheckboxList::make('risk_factors')
                        ->options(PregnancyRiskFactor::class)
                        ->columns(2),
                    Grid::make(2)->schema([
                        Toggle::make('risk_override')
                            ->label('Manual risk override')
                            ->live(),
                        Select::make('risk_level')
                            ->options(RiskLevel::class)
                            ->visible(fn (Get $get): bool => (bool) $get('risk_override')),
                    ]),
                ]),
        ]);
    }
}
