<?php

namespace Modules\MCH\Http\Controllers\Api;

use Modules\Core\Http\Controllers\Api\ResourceApiController;
use Modules\MCH\Models\Vaccine;

/**
 * @group Maternal and child health
 *
 * The vaccine catalogue (read-only).
 */
class VaccineController extends ResourceApiController
{
    protected string $branchScope = 'none';

    protected string $defaultSort = 'name';

    public static function model(): string
    {
        return Vaccine::class;
    }

    public function fields(): array
    {
        return ['id', 'name', 'antigen', 'route', 'site', 'presentation', 'is_active'];
    }

    public function filters(): array
    {
        return ['antigen' => 'antigen', 'is_active' => 'is_active'];
    }

    public function searchable(): array
    {
        return ['name'];
    }

    public function sortable(): array
    {
        return ['name'];
    }
}
