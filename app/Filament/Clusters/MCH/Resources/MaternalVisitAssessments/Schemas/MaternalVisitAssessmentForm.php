<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
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
                    ...self::quickElements(),
                ]),
        ]);
    }

    /**
     * Assessment fields without the encounter/episode selects, for use where
     * the patient context is already known (MCH workspace).
     *
     * @return array<int, Component>
     */
    public static function quickElements(): array
    {
        return [
            TextInput::make('visit_number')
                ->numeric()
                ->minValue(1)
                ->helperText('Leave blank to number automatically.'),
            TextInput::make('ga_weeks')
                ->numeric()
                ->minValue(0)
                ->label('GA weeks')
                ->helperText('Leave blank to derive from LMP.'),
            TextInput::make('ga_days')->numeric()->minValue(0)->maxValue(6)->label('GA days'),
            TextInput::make('fetal_heart_rate')->numeric()->minValue(60)->maxValue(220),
            Select::make('presentation')->options(MaternalPresentation::class),
            Select::make('edema')->options(Edema::class),
            Select::make('urine_protein')->options(UrineResult::class),
            Select::make('urine_glucose')->options(UrineResult::class),
            TextInput::make('fundal_height')
                ->numeric()
                ->minValue(0)
                ->label('Fundal height (cm)')
                ->dehydrated(),
            DatePicker::make('return_date'),
            CheckboxList::make('danger_signs')
                ->options(DangerSign::class)
                ->columns(2)
                ->columnSpanFull(),
            TagsInput::make('drugs_given')
                ->label('Drugs given')
                ->placeholder('Add drug')
                ->columnSpanFull(),
            Toggle::make('referral_required'),
            TextInput::make('referral_destination')->maxLength(255),
            Textarea::make('notes')->columnSpanFull(),
        ];
    }

    /**
     * Maternal BP and weight are not stored on the assessment; the service writes
     * them through Clinical's VitalSign store against the same encounter.
     *
     * @return array<int, Component>
     */
    public static function vitalsElements(): array
    {
        return [
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('systolic_bp')->label('Systolic BP')->suffix('mmHg')->numeric()->minValue(40)->maxValue(300),
                    TextInput::make('diastolic_bp')->label('Diastolic BP')->suffix('mmHg')->numeric()->minValue(20)->maxValue(200),
                    TextInput::make('weight')->label('Weight')->suffix('kg')->numeric()->step(0.1)->minValue(0),
                ]),
        ];
    }
}
