<?php

namespace Modules\MCH\Filament\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Modules\MCH\Enums\BirthOutcome;
use Modules\MCH\Enums\DeliveryMode;
use Modules\MCH\Enums\DeliveryPlace;
use Modules\Patient\Enums\Gender;

/**
 * Delivery (labour outcome) form used by the MCH workspace "Record delivery" action.
 */
class DeliveryRecordForm
{
    /** @var array<string, string> */
    public const COMPLICATIONS = [
        'pph' => 'Postpartum haemorrhage',
        'pre_eclampsia' => 'Pre-eclampsia / eclampsia',
        'obstructed_labour' => 'Obstructed / prolonged labour',
        'ruptured_uterus' => 'Ruptured uterus',
        'sepsis' => 'Sepsis',
        'retained_placenta' => 'Retained placenta',
        'perineal_tear' => 'Perineal tear (3rd/4th degree)',
    ];

    /**
     * @return array<int, mixed>
     */
    public static function fields(): array
    {
        return [
            Section::make(__('Delivery'))
                ->columns(3)
                ->schema([
                    DateTimePicker::make('delivered_at')
                        ->label(__('Delivered at'))
                        ->seconds(false)
                        ->default(now())
                        ->maxDate(now()->addMinutes(5))
                        ->required(),
                    Select::make('place')
                        ->label(__('Place'))
                        ->options(DeliveryPlace::class)
                        ->default(DeliveryPlace::FACILITY->value)
                        ->required(),
                    Select::make('mode')
                        ->label(__('Mode of delivery'))
                        ->options(DeliveryMode::class)
                        ->default(DeliveryMode::SPONTANEOUS_VAGINAL->value)
                        ->required(),
                    Select::make('attendant_type')
                        ->label(__('Skilled attendant'))
                        ->options([
                            'doctor' => __('Doctor'),
                            'midwife' => __('Midwife'),
                            'nurse' => __('Nurse'),
                            'tba' => __('Traditional birth attendant'),
                            'none' => __('None'),
                        ]),
                    TextInput::make('gestation_weeks')
                        ->label(__('Gestation (weeks)'))
                        ->numeric()
                        ->integer()
                        ->minValue(20)
                        ->maxValue(45),
                    TextInput::make('blood_loss_ml')
                        ->label(__('Estimated blood loss (ml)'))
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(10000),
                    CheckboxList::make('complications')
                        ->label(__('Maternal complications'))
                        ->options(array_map(fn (string $label): string => __($label), self::COMPLICATIONS))
                        ->columns(2)
                        ->columnSpan(2),
                    Select::make('maternal_outcome')
                        ->label(__('Mother'))
                        ->options([
                            'alive' => __('Alive'),
                            'referred' => __('Referred'),
                            'died' => __('Died'),
                        ])
                        ->default('alive')
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('Notes'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Repeater::make('babies')
                ->label(__('Babies'))
                ->minItems(1)
                ->maxItems(6)
                ->defaultItems(1)
                ->addActionLabel(__('Add baby (multiple birth)'))
                ->schema([
                    Grid::make(4)->schema([
                        Select::make('sex')
                            ->label(__('Sex'))
                            ->options([Gender::MALE->value => __('Male'), Gender::FEMALE->value => __('Female')])
                            ->required(),
                        Select::make('outcome')
                            ->label(__('Outcome'))
                            ->options(BirthOutcome::class)
                            ->default(BirthOutcome::LIVE_BIRTH->value)
                            ->required(),
                        TextInput::make('birth_weight_g')
                            ->label(__('Birth weight (g)'))
                            ->numeric()
                            ->integer()
                            ->minValue(300)
                            ->maxValue(7000),
                        Toggle::make('resuscitated')
                            ->label(__('Resuscitated'))
                            ->inline(false),
                        TextInput::make('length_cm')
                            ->label(__('Length (cm)'))
                            ->numeric()
                            ->minValue(25)
                            ->maxValue(65),
                        TextInput::make('head_circumference_cm')
                            ->label(__('Head circ. (cm)'))
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(45),
                        TextInput::make('apgar_1')
                            ->label(__('APGAR 1 min'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(10),
                        TextInput::make('apgar_5')
                            ->label(__('APGAR 5 min'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(10),
                    ]),
                ]),
        ];
    }
}
