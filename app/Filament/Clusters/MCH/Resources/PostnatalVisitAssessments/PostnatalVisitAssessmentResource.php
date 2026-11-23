<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\PostnatalVisitAssessments;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Enums\NavigationGroup;
use Modules\Core\Filament\Support\ClientIdentityColumn;
use Modules\MCH\Enums\PostnatalContact;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\PostnatalVisitAssessments\Pages\ListPostnatalVisitAssessments;
use Modules\MCH\Filament\Clusters\MCH\Resources\PostnatalVisitAssessments\Pages\ViewPostnatalVisitAssessment;
use Modules\MCH\Models\PostnatalVisitAssessment;

/**
 * PNC register (read-only; visits are recorded from the MCH workspace).
 */
class PostnatalVisitAssessmentResource extends Resource
{
    protected static ?string $model = PostnatalVisitAssessment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::CLINICAL;

    protected static ?string $cluster = MchCluster::class;

    protected static ?string $navigationLabel = 'PNC visits';

    protected static ?string $modelLabel = 'PNC visit';

    protected static ?string $pluralModelLabel = 'PNC visits';

    protected static ?string $slug = 'pnc-visits';

    protected static ?int $navigationSort = 5;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Postnatal visit'))
                ->columns(3)
                ->schema([
                    TextEntry::make('patient.full_name')->label(__('Patient')),
                    TextEntry::make('subject')->label(__('For'))->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    TextEntry::make('contact')->label(__('Contact'))->badge(),
                    TextEntry::make('days_postpartum')->label(__('Days postpartum'))->placeholder('—'),
                    TextEntry::make('temperature')->label(__('Temperature'))->suffix(' °C')->placeholder('—'),
                    TextEntry::make('systolic_bp')->label(__('BP'))->formatStateUsing(fn ($state, $record): string => $record->systolic_bp.'/'.$record->diastolic_bp)->placeholder('—'),
                    TextEntry::make('uterus')->label(__('Uterus'))->placeholder('—'),
                    TextEntry::make('lochia')->label(__('Lochia'))->placeholder('—'),
                    TextEntry::make('breastfeeding')->label(__('Breastfeeding'))->placeholder('—'),
                    TextEntry::make('cord')->label(__('Cord'))->placeholder('—'),
                    TextEntry::make('danger_signs')->label(__('Danger signs'))->badge()->color('danger')->placeholder(__('None')),
                    IconEntry::make('family_planning_counselled')->label(__('FP counselled'))->boolean(),
                    TextEntry::make('family_planning_method')->label(__('FP method'))->placeholder('—'),
                    IconEntry::make('referral_required')->label(__('Referral'))->boolean(),
                    TextEntry::make('referral_destination')->label(__('Referred to'))->placeholder('—'),
                    TextEntry::make('notes')->label(__('Notes'))->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('patient'))
            ->columns([
                ClientIdentityColumn::make(label: 'Patient'),
                TextColumn::make('created_at')->label(__('Date'))->date()->sortable(),
                TextColumn::make('subject')->label(__('For'))->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('contact')->label(__('Contact'))->badge(),
                TextColumn::make('days_postpartum')->label(__('Day'))->placeholder('—'),
                IconColumn::make('referral_required')->label(__('Referred'))->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('contact')->options(PostnatalContact::class),
                SelectFilter::make('subject')->options(['mother' => __('Mother'), 'baby' => __('Baby')]),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostnatalVisitAssessments::route('/'),
            'view' => ViewPostnatalVisitAssessment::route('/{record}'),
        ];
    }
}
