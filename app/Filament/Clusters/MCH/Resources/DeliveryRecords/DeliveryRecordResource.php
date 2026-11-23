<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\DeliveryRecords;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Enums\NavigationGroup;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Enums\DeliveryMode;
use Modules\MCH\Enums\DeliveryPlace;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\DeliveryRecords\Pages\ListDeliveryRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\DeliveryRecords\Pages\ViewDeliveryRecord;
use Modules\MCH\Filament\Schemas\DeliveryRecordForm;
use Modules\MCH\Models\DeliveryRecord;

/**
 * Delivery register (read-only; deliveries are recorded from the MCH workspace).
 */
class DeliveryRecordResource extends Resource
{
    protected static ?string $model = DeliveryRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'Deliveries';

    protected static ?string $modelLabel = 'delivery';

    protected static ?string $pluralModelLabel = 'deliveries';

    protected static ?string $slug = 'deliveries';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Delivery'))
                ->columns(3)
                ->schema([
                    TextEntry::make('patient.full_name')->label(__('Mother')),
                    TextEntry::make('delivered_at')->label(__('Delivered at'))->dateTime(),
                    TextEntry::make('mode')->label(__('Mode')),
                    TextEntry::make('place')->label(__('Place')),
                    TextEntry::make('attendant_type')->label(__('Attendant'))->placeholder('—'),
                    TextEntry::make('gestation_weeks')->label(__('Gestation (weeks)'))->placeholder('—'),
                    TextEntry::make('blood_loss_ml')->label(__('Blood loss (ml)'))->placeholder('—'),
                    TextEntry::make('maternal_outcome')->label(__('Mother'))->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    TextEntry::make('complications')
                        ->label(__('Complications'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __(DeliveryRecordForm::COMPLICATIONS[$state] ?? $state))
                        ->placeholder(__('None')),
                    TextEntry::make('notes')->label(__('Notes'))->placeholder('—')->columnSpanFull(),
                ]),
            Section::make(__('Babies'))
                ->schema([
                    RepeatableEntry::make('newborns')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('birth_order')->label('#'),
                            TextEntry::make('sex')->label(__('Sex')),
                            TextEntry::make('outcome')->label(__('Outcome'))->badge(),
                            TextEntry::make('birth_weight_g')->label(__('Birth weight'))->suffix(' g')->placeholder('—'),
                            TextEntry::make('apgar_1')->label(__('APGAR 1/5'))->formatStateUsing(fn ($state, $record): string => ($record->apgar_1 ?? '—').' / '.($record->apgar_5 ?? '—')),
                            TextEntry::make('length_cm')->label(__('Length'))->suffix(' cm')->placeholder('—'),
                            TextEntry::make('head_circumference_cm')->label(__('Head circ.'))->suffix(' cm')->placeholder('—'),
                            TextEntry::make('child.mrn')->label(__('Registered as'))->placeholder('—'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['patient', 'newborns']))
            ->columns([
                ClientIdentityColumn::make(label: 'Mother'),
                TextColumn::make('delivered_at')->label(__('Delivered at'))->dateTime()->sortable(),
                TextColumn::make('mode')->label(__('Mode'))->badge(),
                TextColumn::make('place')->label(__('Place')),
                TextColumn::make('number_of_babies')->label(__('Babies')),
                TextColumn::make('live_births')
                    ->label(__('Live births'))
                    ->state(fn (DeliveryRecord $record): int => $record->newborns->filter(fn ($baby) => $baby->outcome->isLive())->count()),
                TextColumn::make('maternal_outcome')->label(__('Mother'))->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->defaultSort('delivered_at', 'desc')
            ->filters([
                SelectFilter::make('mode')->options(DeliveryMode::class),
                SelectFilter::make('place')->options(DeliveryPlace::class),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryRecords::route('/'),
            'view' => ViewDeliveryRecord::route('/{record}'),
        ];
    }
}
