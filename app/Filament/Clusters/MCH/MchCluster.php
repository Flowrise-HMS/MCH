<?php

namespace Modules\MCH\Filament\Clusters\MCH;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Enums\SidebarGroup;

class MchCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = SidebarGroup::PatientCare;

    protected static ?string $navigationLabel = 'MCH';

    protected static ?string $clusterBreadcrumb = 'MCH';

    protected static ?string $slug = 'mch';

    protected static ?int $navigationSort = 40;
}
