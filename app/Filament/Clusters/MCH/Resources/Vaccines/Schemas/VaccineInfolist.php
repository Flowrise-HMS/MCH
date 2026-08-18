<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VaccineInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vaccine')
                ->columns(2)
                ->schema([
                    TextEntry::make('antigen')->badge(),
                    TextEntry::make('name'),
                    TextEntry::make('route')->placeholder('-'),
                    TextEntry::make('site')->placeholder('-'),
                    TextEntry::make('presentation')->placeholder('-'),
                    IconEntry::make('is_active')->boolean(),
                ]),
        ]);
    }
}
