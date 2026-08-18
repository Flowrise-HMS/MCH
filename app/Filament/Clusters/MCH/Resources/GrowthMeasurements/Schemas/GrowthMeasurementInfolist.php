<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GrowthMeasurementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Measurement')
                ->columns(2)
                ->schema([
                    TextEntry::make('patient.full_name')->label('Patient'),
                    TextEntry::make('patient.mrn')->label('MRN'),
                    TextEntry::make('encounter.encounter_number')->label('Encounter')->placeholder('-'),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('value'),
                    TextEntry::make('unit'),
                    TextEntry::make('date')->date(),
                ]),
            Section::make('Timestamps')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('updated_at')->dateTime(),
                ]),
        ]);
    }
}
