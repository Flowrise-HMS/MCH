<?php

namespace Modules\MCH\Filament\Clusters\Workspace;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Enums\SidebarGroup;

class MchWorkspaceCluster extends Cluster
{
    protected static ?string $slug = 'mch-workspace';

    protected static ?string $title = 'MCH Workspace';

    protected static ?string $navigationLabel = 'MCH Workspace';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = SidebarGroup::Workspaces;

    protected static ?int $navigationSort = 20;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static bool $shouldRegisterSubNavigation = false;

    public static function canAccess(): bool
    {
        return static::canAccessClusteredComponents();
    }
}
