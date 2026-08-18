<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\MCH\Enums\ImmunizationStatus;

class ImmunizationRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Immunization')
                ->columns(2)
                ->schema([
                    Select::make('patient_id')
                        ->relationship('patient', 'mrn')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record?->full_name ?? 'Select patient')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Patient'),
                    Select::make('vaccine_id')
                        ->relationship('vaccine', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('dose_sequence')->numeric()->required()->minValue(1)->default(1),
                    Select::make('status')
                        ->options(ImmunizationStatus::class)
                        ->required()
                        ->live(),
                    DatePicker::make('administered_date')
                        ->visible(fn (Get $get): bool => $get('status') === ImmunizationStatus::ADMINISTERED->value),
                    TextInput::make('batch_lot'),
                    TextInput::make('site'),
                    TextInput::make('route'),
                    TextInput::make('reason')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => in_array($get('status'), [
                            ImmunizationStatus::DECLINED->value,
                            ImmunizationStatus::CONTRAINDICATED->value,
                        ], true)),
                ]),
        ]);
    }
}
