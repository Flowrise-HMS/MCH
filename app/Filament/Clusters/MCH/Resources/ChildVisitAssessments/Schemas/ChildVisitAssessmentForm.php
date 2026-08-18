<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Clinical\Enums\EncounterType;
use Modules\MCH\Enums\DevelopmentalScreen;
use Modules\MCH\Enums\FeedingMethod;
use Modules\MCH\Enums\GrowthMeasurementType;

class ChildVisitAssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visit')
                ->columns(2)
                ->schema([
                    Select::make('encounter_id')
                        ->relationship(
                            'encounter',
                            'encounter_number',
                            fn ($query) => $query->where('type', EncounterType::CHILD_WELFARE)->with('patient'),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => ($record->encounter_number ?? $record->id).' — '.($record->patient?->full_name ?? 'Unknown'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Child-welfare encounter'),
                    Select::make('child_health_record_id')
                        ->relationship('childHealthRecord', 'id')
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => $record->patient?->full_name ?? (string) $record->id,
                        )
                        ->searchable()
                        ->preload()
                        ->label('CWC record'),
                    Select::make('feeding')->options(FeedingMethod::class),
                    Select::make('developmental_screen')->options(DevelopmentalScreen::class),
                    Toggle::make('vitamin_a_given')->label('Vitamin A given'),
                    Toggle::make('dewormed'),
                    Toggle::make('referral_required'),
                    TextInput::make('referral_destination')->maxLength(255),
                    Textarea::make('notes')->columnSpanFull(),
                ]),
            Section::make('Anthropometry')
                ->schema([
                    Repeater::make('measurements')
                        ->schema([
                            Select::make('type')
                                ->options(
                                    collect(GrowthMeasurementType::cases())
                                        ->filter(fn (GrowthMeasurementType $type): bool => $type->isChildAnthropometry())
                                        ->mapWithKeys(fn (GrowthMeasurementType $type): array => [$type->value => $type->getLabel()])
                                        ->all(),
                                )
                                ->required(),
                            TextInput::make('value')->numeric()->required()->minValue(0),
                            TextInput::make('unit')->required()->maxLength(8)->default('kg'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->dehydrated(),
                ]),
        ]);
    }
}
