<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages\CreateMchRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages\EditMchRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages\ListMchRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Schemas\MchRecordForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Tables\MchRecordsTable;
use Modules\MCH\Models\MchRecord;

class MchRecordResource extends Resource
{
    protected static ?string $model = MchRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'MCH books';

    protected static ?string $modelLabel = 'MCH book';

    protected static ?string $pluralModelLabel = 'MCH books';

    protected static ?string $slug = 'mch-books';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return MchRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MchRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMchRecords::route('/'),
            'create' => CreateMchRecord::route('/create'),
            'edit' => EditMchRecord::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner']);
    }
}
