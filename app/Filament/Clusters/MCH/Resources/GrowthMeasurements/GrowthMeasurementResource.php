<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\CreateGrowthMeasurement;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\EditGrowthMeasurement;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\ListGrowthMeasurements;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\ViewGrowthMeasurement;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Schemas\GrowthMeasurementForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Schemas\GrowthMeasurementInfolist;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Tables\GrowthMeasurementsTable;
use Modules\MCH\Models\GrowthMeasurement;

class GrowthMeasurementResource extends Resource
{
    protected static ?string $model = GrowthMeasurement::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'Growth measurements';

    protected static ?string $slug = 'growth-measurements';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return GrowthMeasurementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GrowthMeasurementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrowthMeasurementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrowthMeasurements::route('/'),
            'create' => CreateGrowthMeasurement::route('/create'),
            'view' => ViewGrowthMeasurement::route('/{record}'),
            'edit' => EditGrowthMeasurement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient', 'encounter']);
    }
}
