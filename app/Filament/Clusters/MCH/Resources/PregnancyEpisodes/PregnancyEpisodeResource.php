<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages\CreatePregnancyEpisode;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages\EditPregnancyEpisode;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages\ListPregnancyEpisodes;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Schemas\PregnancyEpisodeForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Tables\PregnancyEpisodesTable;
use Modules\MCH\Models\PregnancyEpisode;

class PregnancyEpisodeResource extends Resource
{
    protected static ?string $model = PregnancyEpisode::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'Pregnancy registry';

    protected static ?string $modelLabel = 'pregnancy episode';

    protected static ?string $pluralModelLabel = 'pregnancy episodes';

    protected static ?string $slug = 'pregnancy-episodes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PregnancyEpisodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PregnancyEpisodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPregnancyEpisodes::route('/'),
            'create' => CreatePregnancyEpisode::route('/create'),
            'edit' => EditPregnancyEpisode::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient']);
    }
}
