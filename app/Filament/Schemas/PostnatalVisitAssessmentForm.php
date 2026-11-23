<?php

namespace Modules\MCH\Filament\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Modules\MCH\Classes\Services\PostnatalVisitAssessmentService;
use Modules\MCH\Enums\PostnatalContact;

/**
 * Postnatal check form; `$subject` is "mother" or "baby".
 */
class PostnatalVisitAssessmentForm
{
    /**
     * @return array<int, mixed>
     */
    public static function fields(string $subject = 'mother'): array
    {
        $isMother = $subject === 'mother';
        $signs = array_combine(
            PostnatalVisitAssessmentService::DANGER_SIGNS,
            array_map(fn (string $sign): string => __(ucfirst(str_replace('_', ' ', $sign))), PostnatalVisitAssessmentService::DANGER_SIGNS),
        );

        return [
            Grid::make(3)->schema([
                Select::make('contact')
                    ->label(__('Contact'))
                    ->options(PostnatalContact::class)
                    ->placeholder(__('Derive from days since delivery')),
                TextInput::make('temperature')
                    ->label(__('Temperature (°C)'))
                    ->numeric()
                    ->minValue(30)
                    ->maxValue(45),
                Select::make('breastfeeding')
                    ->label(__('Breastfeeding'))
                    ->options([
                        'exclusive' => __('Exclusive'),
                        'mixed' => __('Mixed'),
                        'none' => __('Not breastfeeding'),
                        'difficulty' => __('Having difficulty'),
                    ]),
                TextInput::make('systolic_bp')->label(__('Systolic BP'))->numeric()->integer()->minValue(50)->maxValue(260)->visible($isMother),
                TextInput::make('diastolic_bp')->label(__('Diastolic BP'))->numeric()->integer()->minValue(30)->maxValue(160)->visible($isMother),
                Select::make('uterus')
                    ->label(__('Uterus'))
                    ->options(['involuting' => __('Well contracted / involuting'), 'boggy' => __('Soft / boggy'), 'tender' => __('Tender')])
                    ->visible($isMother),
                Select::make('lochia')
                    ->label(__('Lochia'))
                    ->options(['normal' => __('Normal'), 'heavy' => __('Heavy'), 'offensive' => __('Offensive')])
                    ->visible($isMother),
                Select::make('cord')
                    ->label(__('Cord'))
                    ->options(['clean' => __('Clean / dry'), 'infected' => __('Signs of infection'), 'separated' => __('Separated')])
                    ->visible(! $isMother),
            ]),
            CheckboxList::make('danger_signs')
                ->label(__('Danger signs'))
                ->options($signs)
                ->columns(3),
            Grid::make(3)->schema([
                Toggle::make('family_planning_counselled')
                    ->label(__('Family planning counselled'))
                    ->live()
                    ->visible($isMother),
                TextInput::make('family_planning_method')
                    ->label(__('Method chosen'))
                    ->visible(fn (Get $get): bool => $isMother && (bool) $get('family_planning_counselled')),
                Toggle::make('referral_required')
                    ->label(__('Referral required'))
                    ->live(),
                TextInput::make('referral_destination')
                    ->label(__('Referred to'))
                    ->visible(fn (Get $get): bool => (bool) $get('referral_required')),
            ]),
            Textarea::make('notes')->label(__('Notes'))->rows(2),
        ];
    }
}
