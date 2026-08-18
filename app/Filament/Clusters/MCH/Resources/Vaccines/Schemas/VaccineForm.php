<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\MCH\Enums\VaccineAntigen;

class VaccineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vaccine')
                ->columns(2)
                ->schema([
                    Select::make('antigen')
                        ->options(VaccineAntigen::class)
                        ->required()
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')->required(),
                    TextInput::make('route'),
                    TextInput::make('site'),
                    TextInput::make('presentation'),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }
}
