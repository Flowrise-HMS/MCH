<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Modules\MCH\Filament\Support\InfolistFormat;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;

class MchRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Book')
                ->columns(2)
                ->schema([
                    TextEntry::make('serial_number')->label('Serial number'),
                    TextEntry::make('unit')->badge(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('issue_date')->date(),
                    TextEntry::make('branch.name')->label('Branch')->placeholder('-'),
                ]),
            Section::make('Owner')
                ->columns(2)
                ->schema([
                    TextEntry::make('owner_type')
                        ->label('Owner type')
                        ->formatStateUsing(fn (?string $state): string => class_basename((string) $state)),
                    TextEntry::make('owner_label')
                        ->label('Owner')
                        ->state(fn (MchRecord $record): string => self::ownerLabel($record)),
                ]),
            Section::make('Consent & replacement')
                ->columns(2)
                ->schema([
                    TextEntry::make('data_consented')
                        ->label('Data consented')
                        ->formatStateUsing(fn (?bool $state): string => InfolistFormat::yesNo($state)),
                    TextEntry::make('consented_at')->dateTime()->placeholder('-'),
                    TextEntry::make('consentingUser.name')->label('Consented by')->placeholder('-'),
                    TextEntry::make('replacement.serial_number')
                        ->label('Replaced by')
                        ->placeholder('-'),
                ]),
            Section::make('Timestamps')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('updated_at')->dateTime(),
                ]),
        ]);
    }

    private static function ownerLabel(MchRecord $record): string
    {
        $owner = $record->owner;

        if (! $owner instanceof Model) {
            return '-';
        }

        if ($owner instanceof PregnancyEpisode || $owner instanceof ChildHealthRecord) {
            $name = $owner->patient?->full_name ?? $owner->getKey();
            $mrn = $owner->patient?->mrn;

            return $mrn ? $name.' ('.$mrn.')' : (string) $name;
        }

        return (string) $owner->getKey();
    }
}
