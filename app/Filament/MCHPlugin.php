<?php

namespace Modules\MCH\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class MCHPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'MCH';
    }

    public function getId(): string
    {
        return 'mch';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
