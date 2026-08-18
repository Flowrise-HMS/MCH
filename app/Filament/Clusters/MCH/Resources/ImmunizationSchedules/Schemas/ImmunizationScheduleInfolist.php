<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImmunizationScheduleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Schedule')
                ->columns(2)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('target_population')->badge(),
                    TextEntry::make('description')->placeholder('-')->columnSpanFull(),
                    IconEntry::make('is_active')->boolean(),
                ]),
            Section::make('Doses')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            TextEntry::make('vaccine.name')->label('Vaccine'),
                            TextEntry::make('dose_sequence')->label('Dose'),
                            TextEntry::make('minimum_age_days')->label('Min age (days)'),
                            TextEntry::make('label')->placeholder('-'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }
}
