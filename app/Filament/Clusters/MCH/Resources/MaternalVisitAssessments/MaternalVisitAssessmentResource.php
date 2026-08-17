<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages\CreateMaternalVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages\EditMaternalVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages\ListMaternalVisitAssessments;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Schemas\MaternalVisitAssessmentForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Tables\MaternalVisitAssessmentsTable;
use Modules\MCH\Models\MaternalVisitAssessment;

class MaternalVisitAssessmentResource extends Resource
{
    protected static ?string $model = MaternalVisitAssessment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'ANC visits';

    protected static ?string $modelLabel = 'ANC visit';

    protected static ?string $pluralModelLabel = 'ANC visits';

    protected static ?string $slug = 'anc-visits';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return MaternalVisitAssessmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaternalVisitAssessmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaternalVisitAssessments::route('/'),
            'create' => CreateMaternalVisitAssessment::route('/create'),
            'edit' => EditMaternalVisitAssessment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient', 'encounter', 'pregnancyEpisode']);
    }
}
