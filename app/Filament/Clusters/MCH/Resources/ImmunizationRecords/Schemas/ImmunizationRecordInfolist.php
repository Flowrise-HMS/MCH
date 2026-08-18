<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImmunizationRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Immunization')
                ->columns(2)
                ->schema([
                    TextEntry::make('patient.full_name')->label('Patient'),
                    TextEntry::make('patient.mrn')->label('MRN'),
                    TextEntry::make('vaccine.name')->label('Vaccine'),
                    TextEntry::make('dose_sequence')->label('Dose'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('administered_date')->date()->placeholder('-'),
                    TextEntry::make('batch_lot')->placeholder('-'),
                    TextEntry::make('site')->placeholder('-'),
                    TextEntry::make('route')->placeholder('-'),
                    TextEntry::make('reason')->placeholder('-')->columnSpanFull(),
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
