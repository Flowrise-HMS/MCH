<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\CreateImmunizationSchedule;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\EditImmunizationSchedule;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\ListImmunizationSchedules;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\ViewImmunizationSchedule;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Schemas\ImmunizationScheduleForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Schemas\ImmunizationScheduleInfolist;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Tables\ImmunizationSchedulesTable;
use Modules\MCH\Models\ImmunizationSchedule;

class ImmunizationScheduleResource extends Resource
{
    protected static ?string $model = ImmunizationSchedule::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'EPI schedules';

    protected static ?string $modelLabel = 'immunization schedule';

    protected static ?string $pluralModelLabel = 'immunization schedules';

    protected static ?string $slug = 'immunization-schedules';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return ImmunizationScheduleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImmunizationScheduleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmunizationSchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmunizationSchedules::route('/'),
            'create' => CreateImmunizationSchedule::route('/create'),
            'view' => ViewImmunizationSchedule::route('/{record}'),
            'edit' => EditImmunizationSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['items.vaccine'])
            ->withCount('items');
    }
}
