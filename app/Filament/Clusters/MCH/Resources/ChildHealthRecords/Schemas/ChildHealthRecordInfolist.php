<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChildHealthRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Child')
                ->columns(2)
                ->schema([
                    TextEntry::make('patient.full_name')->label('Patient'),
                    TextEntry::make('patient.mrn')->label('MRN'),
                    TextEntry::make('date_of_birth')->date()->placeholder('-'),
                    TextEntry::make('status')->badge(),
                ]),
            Section::make('Linkage')
                ->columns(2)
                ->schema([
                    TextEntry::make('pregnancyEpisode.patient.full_name')
                        ->label('Linked pregnancy (mother)')
                        ->placeholder('-'),
                    TextEntry::make('pregnancyEpisode.edd')
                        ->label('Mother EDD')
                        ->date()
                        ->placeholder('-'),
                ]),
            Section::make('Notes')
                ->schema([
                    TextEntry::make('notes')->placeholder('No notes recorded'),
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
