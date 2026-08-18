<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\VaccineAntigen;

class Vaccine extends BaseModel
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'antigen',
        'name',
        'route',
        'site',
        'presentation',
        'is_active',
    ];

    protected $casts = [
        'antigen' => VaccineAntigen::class,
        'is_active' => 'boolean',
    ];

    protected static function bootBelongsToBranch(): void
    {
        // Vaccines are facility-wide catalogues — no branch_id.
    }
}
