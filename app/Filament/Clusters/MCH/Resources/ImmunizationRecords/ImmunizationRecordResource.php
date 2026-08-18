<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\CreateImmunizationRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\EditImmunizationRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\ListImmunizationRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\ViewImmunizationRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Schemas\ImmunizationRecordForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Schemas\ImmunizationRecordInfolist;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Tables\ImmunizationRecordsTable;
use Modules\MCH\Models\ImmunizationRecord;

class ImmunizationRecordResource extends Resource
{
    protected static ?string $model = ImmunizationRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'Immunizations';

    protected static ?string $modelLabel = 'immunization record';

    protected static ?string $pluralModelLabel = 'immunization records';

    protected static ?string $slug = 'immunization-records';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return ImmunizationRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImmunizationRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmunizationRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmunizationRecords::route('/'),
            'create' => CreateImmunizationRecord::route('/create'),
            'view' => ViewImmunizationRecord::route('/{record}'),
            'edit' => EditImmunizationRecord::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient', 'vaccine']);
    }
}
