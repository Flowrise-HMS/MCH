<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImmunizationScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Schedule')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->unique(ignoreRecord: true),
                    Select::make('target_population')
                        ->options([
                            'child' => 'Child',
                            'maternal' => 'Maternal',
                        ])
                        ->required(),
                    TextInput::make('description')->columnSpanFull(),
                    Toggle::make('is_active')->default(true),
                ]),
            Section::make('Doses')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('vaccine_id')
                                ->relationship('vaccine', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            TextInput::make('dose_sequence')->numeric()->required()->minValue(1),
                            TextInput::make('minimum_age_days')->numeric()->default(0)->minValue(0),
                            TextInput::make('maximum_age_days')->numeric()->minValue(0),
                            TextInput::make('label'),
                        ])
                        ->columns(2)
                        ->defaultItems(0),
                ]),
        ]);
    }
}
