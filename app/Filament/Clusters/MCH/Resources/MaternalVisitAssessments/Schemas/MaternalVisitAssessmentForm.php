<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Clinical\Enums\EncounterType;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\Edema;
use Modules\MCH\Enums\MaternalPresentation;
use Modules\MCH\Enums\UrineResult;

class MaternalVisitAssessmentForm
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
                            fn ($query) => $query->where('type', EncounterType::ANTENATAL)->with('patient'),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => ($record->encounter_number ?? $record->id).' — '.($record->patient?->full_name ?? 'Unknown'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Antenatal encounter'),
                    Select::make('pregnancy_episode_id')
                        ->relationship('pregnancyEpisode', 'id')
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => ($record->patient?->full_name ?? $record->id).($record->edd ? ' (EDD '.$record->edd->toDateString().')' : ''),
                        )
                        ->searchable()
                        ->preload()
                        ->label('Pregnancy episode'),
                    TextInput::make('visit_number')->numeric()->minValue(1),
                    TextInput::make('ga_weeks')->numeric()->minValue(0)->label('GA weeks'),
                    TextInput::make('ga_days')->numeric()->minValue(0)->maxValue(6)->label('GA days'),
                    TextInput::make('fetal_heart_rate')->numeric()->minValue(0),
                    Select::make('presentation')->options(MaternalPresentation::class),
                    Select::make('edema')->options(Edema::class),
                    Select::make('urine_protein')->options(UrineResult::class),
                    Select::make('urine_glucose')->options(UrineResult::class),
                    TextInput::make('fundal_height')
                        ->numeric()
                        ->label('Fundal height (cm)')
                        ->dehydrated(),
                    DatePicker::make('return_date'),
                    CheckboxList::make('danger_signs')
                        ->options(DangerSign::class)
                        ->columns(2)
                        ->columnSpanFull(),
                    Toggle::make('referral_required'),
                    TextInput::make('referral_destination')->maxLength(255),
                    Textarea::make('notes')->columnSpanFull(),
                ]),
        ]);
    }
}
