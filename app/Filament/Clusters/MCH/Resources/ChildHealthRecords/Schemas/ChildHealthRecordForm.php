<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\MCH\Enums\ChildHealthRecordStatus;

class ChildHealthRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Child')
                ->columns(2)
                ->schema([
                    Select::make('patient_id')
                        ->relationship('patient', 'mrn')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record?->full_name ?? 'Select patient')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Patient'),
                    Select::make('pregnancy_episode_id')
                        ->relationship('pregnancyEpisode', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record?->patient?->full_name ?? (string) $record?->id)
                        ->searchable()
                        ->preload()
                        ->label('Linked pregnancy episode'),
                    DatePicker::make('date_of_birth')->label('Date of birth'),
                    Select::make('status')
                        ->options(ChildHealthRecordStatus::class)
                        ->default(ChildHealthRecordStatus::ACTIVE->value)
                        ->required(),
                    Textarea::make('notes')->columnSpanFull(),
                ]),
        ]);
    }
}
