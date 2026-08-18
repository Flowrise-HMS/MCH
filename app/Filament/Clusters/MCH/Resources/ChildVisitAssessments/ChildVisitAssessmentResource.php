<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\NavigationGroup;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\CreateChildVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\EditChildVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\ListChildVisitAssessments;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\ViewChildVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Schemas\ChildVisitAssessmentForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Schemas\ChildVisitAssessmentInfolist;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Tables\ChildVisitAssessmentsTable;
use Modules\MCH\Models\ChildVisitAssessment;

class ChildVisitAssessmentResource extends Resource
{
    protected static ?string $model = ChildVisitAssessment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'CWC visits';

    protected static ?string $modelLabel = 'CWC visit';

    protected static ?string $pluralModelLabel = 'CWC visits';

    protected static ?string $slug = 'cwc-visits';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ChildVisitAssessmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChildVisitAssessmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChildVisitAssessmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChildVisitAssessments::route('/'),
            'create' => CreateChildVisitAssessment::route('/create'),
            'view' => ViewChildVisitAssessment::route('/{record}'),
            'edit' => EditChildVisitAssessment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient', 'encounter', 'childHealthRecord.patient']);
    }
}
