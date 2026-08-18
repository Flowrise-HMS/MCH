<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\MCH\Filament\Support\InfolistFormat;

class ChildVisitAssessmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visit')
                ->columns(2)
                ->schema([
                    TextEntry::make('patient.full_name')->label('Child'),
                    TextEntry::make('encounter.encounter_number')->label('Encounter')->placeholder('-'),
                    TextEntry::make('childHealthRecord.patient.full_name')
                        ->label('CWC record')
                        ->placeholder('-'),
                    TextEntry::make('feeding')->badge()->placeholder('-'),
                    TextEntry::make('developmental_screen')->label('Developmental screen')->badge()->placeholder('-'),
                ]),
            Section::make('Interventions')
                ->columns(2)
                ->schema([
                    TextEntry::make('vitamin_a_given')
                        ->label('Vitamin A given')
                        ->formatStateUsing(fn (?bool $state): string => InfolistFormat::yesNo($state)),
                    TextEntry::make('dewormed')
                        ->formatStateUsing(fn (?bool $state): string => InfolistFormat::yesNo($state)),
                    TextEntry::make('referral_required')
                        ->label('Referral required')
                        ->formatStateUsing(fn (?bool $state): string => InfolistFormat::yesNo($state)),
                    TextEntry::make('referral_destination')->placeholder('-'),
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
