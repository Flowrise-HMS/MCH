<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\CreateVaccine;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\EditVaccine;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\ListVaccines;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\ViewVaccine;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Schemas\VaccineForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Schemas\VaccineInfolist;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Tables\VaccinesTable;
use Modules\MCH\Models\Vaccine;

class VaccineResource extends Resource
{
    protected static ?string $model = Vaccine::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'Vaccines';

    protected static ?string $modelLabel = 'vaccine';

    protected static ?string $pluralModelLabel = 'vaccines';

    protected static ?string $slug = 'vaccines';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return VaccineForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VaccineInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VaccinesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVaccines::route('/'),
            'create' => CreateVaccine::route('/create'),
            'view' => ViewVaccine::route('/{record}'),
            'edit' => EditVaccine::route('/{record}/edit'),
        ];
    }
}
