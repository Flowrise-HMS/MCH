<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\MCH\Enums\GrowthMeasurementType;

class GrowthMeasurementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Measurement')
                ->columns(2)
                ->schema([
                    Select::make('patient_id')
                        ->relationship('patient', 'mrn')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record?->full_name ?? 'Select patient')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('encounter_id')
                        ->relationship('encounter', 'id')
                        ->searchable()
                        ->preload()
                        ->label('Encounter'),
                    Select::make('type')
                        ->options(GrowthMeasurementType::class)
                        ->required(),
                    TextInput::make('value')->numeric()->required(),
                    TextInput::make('unit')->required()->maxLength(8)->default('kg'),
                    DatePicker::make('date')->required()->default(now()),
                ]),
        ]);
    }
}
