<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages\CreateChildHealthRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages\EditChildHealthRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages\ListChildHealthRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Schemas\ChildHealthRecordForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Tables\ChildHealthRecordsTable;
use Modules\MCH\Models\ChildHealthRecord;

class ChildHealthRecordResource extends Resource
{
    protected static ?string $model = ChildHealthRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFaceSmile;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'CWC registry';

    protected static ?string $modelLabel = 'child health record';

    protected static ?string $pluralModelLabel = 'child health records';

    protected static ?string $slug = 'child-health-records';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ChildHealthRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChildHealthRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChildHealthRecords::route('/'),
            'create' => CreateChildHealthRecord::route('/create'),
            'edit' => EditChildHealthRecord::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient', 'pregnancyEpisode']);
    }
}
