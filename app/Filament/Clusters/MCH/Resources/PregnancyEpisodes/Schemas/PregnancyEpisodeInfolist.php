<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Filament\Support\InfolistFormat;

class PregnancyEpisodeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mother')
                ->columns(2)
                ->schema([
                    TextEntry::make('patient.full_name')->label('Patient'),
                    TextEntry::make('patient.mrn')->label('MRN'),
                ]),
            Section::make('Obstetric history')
                ->columns(3)
                ->schema([
                    TextEntry::make('gravida')->placeholder('-'),
                    TextEntry::make('parity')->placeholder('-'),
                    TextEntry::make('multiple_gestation')
                        ->label('Multiple gestation')
                        ->formatStateUsing(fn (?bool $state): string => InfolistFormat::yesNo($state)),
                    TextEntry::make('lmp')->label('LMP')->date()->placeholder('-'),
                    TextEntry::make('edd')->label('EDD')->date()->placeholder('-'),
                    TextEntry::make('edd_source')->label('EDD source')->badge(),
                    TextEntry::make('booking_date')->date()->placeholder('-'),
                    TextEntry::make('booking_ga_weeks')->label('Booking GA (weeks)')->placeholder('-'),
                    TextEntry::make('outcome')->badge(),
                ]),
            Section::make('Risk')
                ->schema([
                    TextEntry::make('risk_level')->badge(),
                    TextEntry::make('risk_override')
                        ->label('Manual override')
                        ->formatStateUsing(fn (?bool $state): string => InfolistFormat::yesNo($state)),
                    TextEntry::make('risk_factors')
                        ->label('Risk factors')
                        ->formatStateUsing(
                            fn (?array $state): string => InfolistFormat::enumLabels($state, PregnancyRiskFactor::class),
                        ),
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
