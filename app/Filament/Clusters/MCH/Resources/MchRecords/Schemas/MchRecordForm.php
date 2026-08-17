<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\PregnancyEpisode;

class MchRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Issue book')
                ->columns(2)
                ->schema([
                    Select::make('owner_type')
                        ->label('Owner')
                        ->options([
                            PregnancyEpisode::class => 'Pregnancy episode (ANC)',
                            ChildHealthRecord::class => 'Child health record (CWC)',
                        ])
                        ->live()
                        ->required()
                        ->disabledOn('edit'),
                    Select::make('owner_id')
                        ->label('Record')
                        ->searchable()
                        ->preload()
                        ->getSearchResultsUsing(fn (string $search, Get $get): array => self::ownerOptions($get('owner_type'), $search))
                        ->getOptionLabelUsing(fn ($value, Get $get): ?string => self::ownerLabel($get('owner_type'), $value))
                        ->options(fn (Get $get): array => self::ownerOptions($get('owner_type')))
                        ->required()
                        ->disabledOn('edit'),
                    Select::make('unit')
                        ->options([
                            'ANC' => 'ANC',
                            'CWC' => 'CWC',
                        ])
                        ->required()
                        ->disabledOn('edit'),
                    Toggle::make('data_consented')
                        ->label('Data consented')
                        ->helperText('Stored only — not enforced in MCH-1.'),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function ownerOptions(?string $ownerType, string $search = ''): array
    {
        $query = match ($ownerType) {
            PregnancyEpisode::class => PregnancyEpisode::query()->with('patient')->latest(),
            ChildHealthRecord::class => ChildHealthRecord::query()->with('patient')->latest(),
            default => null,
        };

        if ($query === null) {
            return [];
        }

        if ($search !== '') {
            $query->whereHas('patient', fn ($patientQuery) => $patientQuery->where('mrn', 'like', '%'.$search.'%'));
        }

        return $query
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Model $record): array => [
                $record->getKey() => self::formatOwner($record),
            ])
            ->all();
    }

    private static function ownerLabel(?string $ownerType, mixed $value): ?string
    {
        if (! is_string($ownerType) || $value === null) {
            return null;
        }

        $record = $ownerType::query()->with('patient')->find($value);

        return $record instanceof Model ? self::formatOwner($record) : null;
    }

    private static function formatOwner(Model $record): string
    {
        $name = $record->patient?->full_name ?? $record->getKey();
        $mrn = $record->patient?->mrn;

        return $mrn ? $name.' ('.$mrn.')' : (string) $name;
    }
}
