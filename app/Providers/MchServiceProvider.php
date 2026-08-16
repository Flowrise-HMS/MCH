<?php

namespace Modules\MCH\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class MchServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'MCH';

    protected string $nameLower = 'mch';

    protected array $providers = [];

    public function boot(): void
    {
        parent::boot();
    }
}
