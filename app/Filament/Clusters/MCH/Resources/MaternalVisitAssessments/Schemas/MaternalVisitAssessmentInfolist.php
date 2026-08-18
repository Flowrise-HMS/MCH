<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Filament\Support\InfolistFormat;

class MaternalVisitAssessmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visit')
                ->columns(2)
                ->schema([
                    TextEntry::make('patient.full_name')->label('Mother'),
                    TextEntry::make('encounter.encounter_number')->label('Encounter')->placeholder('-'),
                    TextEntry::make('pregnancyEpisode.edd')->label('Episode EDD')->date()->placeholder('-'),
                    TextEntry::make('visit_number')->label('Visit #')->placeholder('-'),
                    TextEntry::make('ga_weeks')->label('GA weeks')->placeholder('-'),
                    TextEntry::make('ga_days')->label('GA days')->placeholder('-'),
                    TextEntry::make('return_date')->date()->placeholder('-'),
                ]),
            Section::make('Examination')
                ->columns(3)
                ->schema([
                    TextEntry::make('fetal_heart_rate')->label('FHR')->placeholder('-'),
                    TextEntry::make('presentation')->badge()->placeholder('-'),
                    TextEntry::make('edema')->badge()->placeholder('-'),
                    TextEntry::make('urine_protein')->label('Urine protein')->badge()->placeholder('-'),
                    TextEntry::make('urine_glucose')->label('Urine glucose')->badge()->placeholder('-'),
                ]),
            Section::make('Danger signs & referral')
                ->schema([
                    TextEntry::make('danger_signs')
                        ->label('Danger signs')
                        ->formatStateUsing(
                            fn (?array $state): string => InfolistFormat::enumLabels($state, DangerSign::class),
                        ),
                    TextEntry::make('drugs_given')
                        ->label('Drugs given')
                        ->formatStateUsing(fn (?array $state): string => $state === null || $state === [] ? '-' : implode(', ', $state)),
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
